<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\errors\InvalidFieldException;
use craft\helpers\Queue;
use craft\queue\BaseJob;
use Exception;
use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\TranslationRecord;
use Throwable;

class FetchTranslationFromConnector extends AbstractRetryJob
{
    public const DELAY_IN_SECONDS_INSTANT = 10;
    public const DELAY_IN_SECONDS_VERIFIED = 60 * 5;
    public const PRIORITY = 2048;
    public const TTR = 60 * 30;

    private const RETRY_COUNT = 3;

    /**
     * @var int $liltJobId
     */
    public $liltJobId;

    /**
     * @var int $translationId
     */
    public $translationId;

    /**
     * @inheritdoc
     *
     * @throws ApiException
     * @throws Throwable
     * @throws InvalidFieldException
     */
    public function execute($queue): void
    {
        $job = Job::findOne(['id' => $this->jobId]);
        if (!$job) {
            Craft::error(sprintf('[%s] Job not found: %d', __CLASS__, $this->jobId));

            $this->markAsDone($queue);
            return;
        }

        $translationRecord = TranslationRecord::findOne(['id' => $this->translationId]);
        if (!$translationRecord) {
            Craft::error(sprintf('[%s] Translation not found: %d', __CLASS__, $this->translationId));

            $this->markAsDone($queue);
            return;
        }

        $mutex = Craft::$app->getMutex();
        $mutexKey = __CLASS__ . '_' . __FUNCTION__ . '_' . $this->jobId . '_' . $this->translationId;
        if (!$mutex->acquire($mutexKey)) {
            Craft::error(sprintf('Job %s is already processing job %d', __CLASS__, $this->jobId));

            $this->markAsDone($queue);
            return;
        }

        if (empty($translationRecord->connectorTranslationId)) {
            Craftliltplugin::getInstance()->updateTranslationsConnectorIds->update($job);
        }
        $translationRecord->refresh();

        if (empty($translationRecord->connectorTranslationId)) {
            //TODO: we can push message to fix connector id
            Craft::error(
                sprintf(
                    "Connector translation id is empty for translation:"
                    . "%d source site: %d target site: %d lilt job: %d",
                    $translationRecord->id,
                    $translationRecord->sourceSiteId,
                    $translationRecord->targetSiteId,
                    $job->liltJobId
                )
            );

            $translationRecord->status = TranslationRecord::STATUS_FAILED;
            $translationRecord->save();

            $mutex->release($mutexKey);
            $this->markAsDone($queue);

            return;
        }

        $translationFromConnector = Craftliltplugin::getInstance()->connectorTranslationRepository->findById(
            $translationRecord->connectorTranslationId
        );

        $isTranslationFinished = $this->isTranslationFinished($job, $translationFromConnector);
        $isTranslationFailed = $this->isTranslationFailed($job, $translationFromConnector);

        if ($isTranslationFailed) {
            Craftliltplugin::getInstance()->translationFailedHandler->__invoke(
                $translationFromConnector,
                $job,
                [
                    $translationRecord->elementId => [
                        $translationRecord->targetSiteId => $translationRecord
                    ]
                ]
            );

            Craftliltplugin::getInstance()->updateJobStatusHandler->update($job->id);

            $mutex->release($mutexKey);
            $this->markAsDone($queue);

            return;
        }

        if (!$isTranslationFinished) {
            Queue::push(
                new FetchTranslationFromConnector(
                    [
                        'jobId' => $this->jobId,
                        'liltJobId' => $this->liltJobId,
                        'translationId' => $this->translationId,
                    ]
                ),
                self::PRIORITY,
                self::getDelay($job->translationWorkflow)
            );

            $mutex->release($mutexKey);
            $this->markAsDone($queue);

            return;
        }

        try {
            Craftliltplugin::getInstance()->syncJobFromLiltConnectorHandler->processTranslation(
                $translationFromConnector,
                $job
            );
        } catch (Exception $ex) {
            Craft::error([
                'message' => "Can't fetch translation!",
                'exception_message' => $ex->getMessage(),
                'exception_trace' => $ex->getTrace(),
                'exception' => $ex,
            ]);

            Craftliltplugin::getInstance()->translationFailedHandler->__invoke(
                $translationFromConnector,
                $job,
                [
                    $translationRecord->elementId => [
                        $translationRecord->targetSiteId => $translationRecord
                    ]
                ]
            );

            Craftliltplugin::getInstance()->updateJobStatusHandler->update($job->id);

            $mutex->release($mutexKey);
            $this->markAsDone($queue);
            return;
        }

        Craftliltplugin::getInstance()->updateJobStatusHandler->update($job->id);

        $mutex->release($mutexKey);
        $this->markAsDone($queue);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Craft::t(
            'app',
            sprintf(
                'Fetching translations: %d',
                $this->translationId
            )
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
                'Fetching of translation: {translationId} for jobId: {jobId} liltJobId: {liltJobId} is done',
                [
                    'jobId' => $this->jobId,
                    'liltJobId' => $this->liltJobId,
                    'translationId' => $this->translationId,
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
            'translationId' => $this->translationId,
            'attempt' => $this->attempt + 1
        ]);
    }

    /**
     * @param $job
     * @param TranslationResponse $translationFromConnector
     * @return bool
     */
    private function isTranslationFailed($job, TranslationResponse $translationFromConnector): bool
    {
        return ($job->isInstantFlow() && in_array($translationFromConnector->getStatus(), [
                    TranslationResponse::STATUS_MT_FAILED,
                    TranslationResponse::STATUS_IMPORT_FAILED,
                    TranslationResponse::STATUS_EXPORT_FAILED,
                ], true))
            || ($job->isVerifiedFlow() && in_array($translationFromConnector->getStatus(), [
                    TranslationResponse::STATUS_MT_FAILED,
                    TranslationResponse::STATUS_IMPORT_FAILED,
                    TranslationResponse::STATUS_EXPORT_FAILED,
                ], true));
    }

    /**
     * @param $job
     * @param TranslationResponse $translationFromConnector
     * @return bool
     */
    private function isTranslationFinished($job, TranslationResponse $translationFromConnector): bool
    {
        return (
                $job->isInstantFlow()
                && $translationFromConnector->getStatus() === TranslationResponse::STATUS_MT_COMPLETE)
            || (
                $job->isVerifiedFlow()
                && $translationFromConnector->getStatus() === TranslationResponse::STATUS_EXPORT_COMPLETE
            );
    }

    public static function getDelay(string $flow = CraftliltpluginParameters::TRANSLATION_WORKFLOW_INSTANT): int
    {
        $envDelay = getenv('CRAFT_LILT_PLUGIN_QUEUE_DELAY_IN_SECONDS');
        if (!empty($envDelay) || $envDelay === '0') {
            return (int)$envDelay;
        }

        return strtolower($flow) === strtolower(CraftliltpluginParameters::TRANSLATION_WORKFLOW_INSTANT) ?
            self::DELAY_IN_SECONDS_INSTANT :
            self::DELAY_IN_SECONDS_VERIFIED;
    }
}
