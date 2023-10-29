<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\helpers\Queue;
use craft\queue\BaseJob;
use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;

class FetchJobStatusFromConnector extends AbstractRetryJob
{
    public const DELAY_IN_SECONDS = 5 * 60;
    public const PRIORITY = 1024;
    public const TTR = 60 * 30;

    private const RETRY_COUNT = 3;

    /**
     * @var int $liltJobId
     */
    public $liltJobId;

    /**
     * @inheritdoc
     * @throws ApiException
     */
    public function execute($queue): void
    {
        $jobRecord = JobRecord::findOne(['id' => $this->jobId]);
        $job = Job::findOne(['id' => $this->jobId]);

        if (!$jobRecord) {
            // job was removed, we are done here
            return;
        }

        if (empty($this->liltJobId)) {
            //looks like job is not sent
            Queue::push(
                new SendJobToConnector(['jobId' => $this->jobId]),
                SendJobToConnector::PRIORITY,
                SendJobToConnector::getDelay(),
                SendJobToConnector::TTR
            );

            $this->markAsDone($queue);

            return;
        }

        $liltJob = Craftliltplugin::getInstance()->connectorJobRepository->findOneById($this->liltJobId);
        $isJobFinished = $liltJob->getStatus() !== JobResponse::STATUS_PROCESSING
            && $liltJob->getStatus() !== JobResponse::STATUS_QUEUED;

        $isJobFailed = $liltJob->getStatus() === JobResponse::STATUS_CANCELED
            || $liltJob->getStatus() === JobResponse::STATUS_FAILED;

        if ($isJobFailed) {
            $jobRecord->status = Job::STATUS_FAILED;

            Craftliltplugin::getInstance()->jobLogsRepository->create(
                $jobRecord->id,
                Craft::$app->getUser()->getId(),
                sprintf('Job failed, received status: %s', $liltJob->getStatus())
            );

            $jobRecord->save();

            TranslationRecord::updateAll(
                ['status' => TranslationRecord::STATUS_FAILED],
                ['jobId' => $jobRecord->id]
            );

            Craft::error([
                "message" => sprintf(
                    'Set job %d and translations to status failed due to failed/cancel status from lilt',
                    $jobRecord->id,
                ),
                "jobRecord" => $jobRecord,
            ]);

            $this->markAsDone($queue);
            return;
        }

        if (!$isJobFinished) {
            Queue::push(
                (new FetchJobStatusFromConnector(
                    [
                        'jobId' => $this->jobId,
                        'liltJobId' => $this->liltJobId,
                    ]
                )),
                self::PRIORITY,
                self::getDelay(),
                self::TTR
            );

            $this->markAsDone($queue);

            return;
        }

        $connectorTranslations = Craftliltplugin::getInstance()->connectorTranslationRepository->findByJobId(
            $job->liltJobId
        );

        $connectorTranslationsStatuses = array_map(
            function (TranslationResponse $connectorTranslation) {
                return $connectorTranslation->getStatus();
            },
            $connectorTranslations->getResults()
        );

        $translationFinished =
            $this->isTranslationsFinished($job, $connectorTranslationsStatuses);

        if (!$translationFinished) {
            if (
                in_array(TranslationResponse::STATUS_EXPORT_FAILED, $connectorTranslationsStatuses)
                || in_array(TranslationResponse::STATUS_IMPORT_FAILED, $connectorTranslationsStatuses)
            ) {
                // job failed

                Craftliltplugin::getInstance()->jobLogsRepository->create(
                    $jobRecord->id,
                    null,
                    'Job is failed, one of translations in failed status'
                );

                TranslationRecord::updateAll(
                    ['status' => TranslationRecord::STATUS_FAILED],
                    ['jobId' => $jobRecord->id]
                );

                $jobRecord->status = Job::STATUS_FAILED;
                $jobRecord->save();

                Craft::error([
                    "message" => sprintf(
                        'Set job %d and translations to status failed due to failed status for translation from lilt',
                        $jobRecord->id,
                    ),
                    "jobRecord" => $jobRecord,
                ]);

                Craft::$app->elements->invalidateCachesForElementType(TranslationRecord::class);
                Craft::$app->elements->invalidateCachesForElementType(Job::class);

                $this->markAsDone($queue);

                return;
            }

            Queue::push(
                (new FetchJobStatusFromConnector(
                    [
                        'jobId' => $this->jobId,
                        'liltJobId' => $this->liltJobId,
                    ]
                )),
                self::PRIORITY,
                self::getDelay(),
                self::TTR
            );

            $this->markAsDone($queue);

            return;
        }

        if ($jobRecord->isVerifiedFlow()) {
            #LILT_TRANSLATION_WORKFLOW_VERIFIED

            $jobRecord->status = Job::STATUS_IN_PROGRESS;

            $translations = Craftliltplugin::getInstance()->translationRepository->findByJobId(
                $this->jobId
            );

            Craftliltplugin::getInstance()->updateTranslationsConnectorIds->update($job);

            foreach ($translations as $translation) {
                Queue::push(
                    new FetchTranslationFromConnector(
                        [
                            'jobId' => $this->jobId,
                            'liltJobId' => $this->liltJobId,
                            'translationId' => $translation->id,
                        ]
                    ),
                    FetchTranslationFromConnector::PRIORITY,
                    10, //10 seconds for first job
                    FetchTranslationFromConnector::TTR
                );
            }
        }

        if ($jobRecord->isInstantFlow()) {
            #LILT_TRANSLATION_WORKFLOW_INSTANT

            if (
                $liltJob->getStatus() === JobResponse::STATUS_FAILED
                || $liltJob->getStatus() === JobResponse::STATUS_CANCELED
            ) {
                $jobRecord->status = Job::STATUS_FAILED;

                TranslationRecord::updateAll(
                    ['status' => TranslationRecord::STATUS_FAILED],
                    ['jobId' => $jobRecord->id]
                );

                Craft::error([
                    "message" => sprintf(
                        'Set job %d and translations to status failed due to failed/cancel status from lilt',
                        $jobRecord->id,
                    ),
                    "jobRecord" => $jobRecord,
                ]);

                $this->markAsDone($queue);

                return;
            }

            $translations = Craftliltplugin::getInstance()->translationRepository->findByJobId(
                $this->jobId
            );

            Craftliltplugin::getInstance()->updateTranslationsConnectorIds->update($job);

            foreach ($translations as $translation) {
                Queue::push(
                    new FetchTranslationFromConnector(
                        [
                            'jobId' => $this->jobId,
                            'liltJobId' => $this->liltJobId,
                            'translationId' => $translation->id,
                        ]
                    ),
                    FetchTranslationFromConnector::PRIORITY,
                    10, //10 seconds for first job
                    FetchTranslationFromConnector::TTR
                );
            }
        }

        $jobRecord->save();

        Craft::$app->elements->invalidateCachesForElementType(
            Job::class
        );

        $this->markAsDone($queue);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Craft::t(
            'app',
            sprintf('Fetching lilt job status: %d', $this->liltJobId)
        );
    }

    /**
     * @param $queue
     * @return void
     */
    private function markAsDone($queue): void
    {
        $this->setProgress(
            $queue,
            1,
            Craft::t(
                'app',
                'Fetching of status for jobId: {jobId} liltJobId: {liltJobId} is done',
                [
                    'jobId' => $this->jobId,
                    'liltJobId' => $this->liltJobId,
                ]
            )
        );
    }

    public function canRetry(): bool
    {
        return $this->attempt < self::RETRY_COUNT;
    }

    public function getRetryJob(): BaseJob
    {
        return new self([
            'jobId' => $this->jobId,
            'liltJobId' => $this->liltJobId,
            'attempt' => $this->attempt + 1
        ]);
    }

    public static function getDelay(): int
    {
        $envDelay = getenv('CRAFT_LILT_PLUGIN_QUEUE_DELAY_IN_SECONDS');
        if (!empty($envDelay) || $envDelay === '0') {
            return (int)$envDelay;
        }

        return self::DELAY_IN_SECONDS;
    }

    /**
     * @param Job $job
     * @param array $connectorTranslationsStatuses
     * @return bool
     */
    private function isTranslationsFinished(Job $job, array $connectorTranslationsStatuses): bool
    {
        $connectorTranslationsStatuses = array_unique($connectorTranslationsStatuses);

        return (
                $job->isInstantFlow()
                && count($connectorTranslationsStatuses) === 1
                && $connectorTranslationsStatuses[0] === TranslationResponse::STATUS_MT_COMPLETE)
            || (
                $job->isVerifiedFlow()
                && count($connectorTranslationsStatuses) === 1
                && $connectorTranslationsStatuses[0] === TranslationResponse::STATUS_EXPORT_COMPLETE
            );
    }
}
