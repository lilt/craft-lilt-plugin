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
use LiltConnectorSDK\Model\JobResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use yii\queue\RetryableJobInterface;

class FetchJobStatusFromConnector extends BaseJob implements RetryableJobInterface
{
    public const DELAY_IN_SECONDS = 5 * 60;
    public const PRIORITY = 1024;
    public const TTR = 60 * 30;

    private const RETRY_COUNT = 3;

    /**
     * @var int $jobId
     */
    public $jobId;

    /**
     * @var int $liltJobId
     */
    public $liltJobId;

    /**
     * @inheritdoc
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
        $isTranslationFinished = $liltJob->getStatus() !== JobResponse::STATUS_PROCESSING
            && $liltJob->getStatus() !== JobResponse::STATUS_QUEUED;

        $isTranslationFailed = $liltJob->getStatus() === JobResponse::STATUS_CANCELED
            || $liltJob->getStatus() === JobResponse::STATUS_FAILED;

        if ($isTranslationFailed) {
            $jobRecord->status = Job::STATUS_FAILED;

            Craftliltplugin::getInstance()->jobLogsRepository->create(
                $jobRecord->id,
                Craft::$app->getUser()->getId(),
                sprintf('Job failed, received status: %s', $liltJob->getStatus())
            );

            $jobRecord->save();
            $this->markAsDone($queue);
            return;
        }

        if (!$isTranslationFinished) {
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
                    FetchTranslationFromConnector::DELAY_IN_SECONDS_VERIFIED,
                    FetchTranslationFromConnector::TTR
                );
            }
        }

        if ($jobRecord->isInstantFlow()) {
            #LILT_TRANSLATION_WORKFLOW_INSTANT

            if ($liltJob->getStatus() === JobResponse::STATUS_FAILED) {
                $jobRecord->status = Job::STATUS_FAILED;
            } elseif ($liltJob->getStatus() === JobResponse::STATUS_CANCELED) {
                $jobRecord->status = Job::STATUS_FAILED;
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
        return Craft::t('app', 'Fetching lilt job status');
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

    public function getTtr(): int
    {
        return self::TTR;
    }

    public function canRetry($attempt, $error): bool
    {
        return $attempt < self::RETRY_COUNT;
    }

    public static function getDelay(): int
    {
        $envDelay = getenv('CRAFT_LILT_PLUGIN_QUEUE_DELAY_IN_SECONDS');
        if (!empty($envDelay) || $envDelay === '0') {
            return (int) $envDelay;
        }

        return self::DELAY_IN_SECONDS;
    }
}
