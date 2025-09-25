<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\listeners;

use Craft;
use craft\queue\Queue;
use LiltConnectorSDK\ApiException;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\elements\Translation;
use lilthq\craftliltplugin\LiltLogger;
use lilthq\craftliltplugin\modules\AbstractRetryJob;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\modules\FetchTranslationFromConnector;
use lilthq\craftliltplugin\modules\SendJobToConnector;
use lilthq\craftliltplugin\modules\SendTranslationToConnector;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use yii\base\Event;
use yii\db\Exception;
use yii\queue\ExecEvent;

class AfterErrorListener implements ListenerInterface
{
    private const SUPPORTED_JOBS = [
        FetchJobStatusFromConnector::class,
        FetchTranslationFromConnector::class,
        SendJobToConnector::class,
        SendTranslationToConnector::class,
    ];

    public function register(): void
    {
        Event::on(
            Queue::class,
            Queue::EVENT_AFTER_ERROR,
            [$this, '__invoke']
        );
    }

    private function isEventEligible(Event $event): bool
    {
        if (!$event instanceof ExecEvent) {
            return false;
        }

        if ($event->job === null) {
            return false;
        }

        $jobClass = get_class($event->job);

        return in_array($jobClass, self::SUPPORTED_JOBS);
    }

    /**
     * @var ExecEvent $event
     */
    public function __invoke(Event $event): Event
    {
        if (!$this->isEventEligible($event)) {
            return $event;
        }

        /**
         * @var AbstractRetryJob $queueJob
         */
        $queueJob = $event->job;

        $jobRecord = JobRecord::findOne(['id' => $queueJob->jobId]);

        LiltLogger::error([
            "message" => sprintf(
                'Job %s failed due to: %s',
                get_class($queueJob),
                $event->error->getMessage()
            ),
            "queueJob" => $queueJob,
            "attempt" => $queueJob->attempt,
            "liltJobId" => $jobRecord->liltJobId,
            "pluginJobId" => $jobRecord->id,
            "jobRecord" => $jobRecord
        ]);

        if (!$queueJob->canRetry()) {
            LiltLogger::warning([
                "message" => sprintf(
                    'Job %s can\'t be retried',
                    $event->id
                ),
                "queueJob" => $queueJob,
                "attempt" => $queueJob->attempt,
                "liltJobId" => $jobRecord->liltJobId,
                "pluginJobId" => $jobRecord->id,
                "jobRecord" => $jobRecord
            ]);


            $jobRecord->status = Job::STATUS_FAILED;
            $jobRecord->save();

            TranslationRecord::updateAll(
                ['status' => TranslationRecord::STATUS_FAILED],
                ['jobId' => $jobRecord->id]
            );

            Craft::$app->elements->invalidateCachesForElementType(TranslationRecord::class);
            Craft::$app->elements->invalidateCachesForElementType(Translation::class);
            Craft::$app->elements->invalidateCachesForElementType(Job::class);

            Craft::$app->queue->release(
                (string)$event->id
            );

            LiltLogger::error([
                "message" => sprintf(
                    '[%s] Mark lilt job %d (%d) as failed due to: %s',
                    get_class($queueJob),
                    $jobRecord->liltJobId,
                    $jobRecord->id,
                    $event->error->getMessage()
                ),
                "queueJob" => $queueJob,
                "attempt" => $queueJob->attempt,
                "liltJobId" => $jobRecord->liltJobId,
                "pluginJobId" => $jobRecord->id,
                "jobRecord" => $jobRecord
            ]);

            if (property_exists($queueJob, 'attempt')) {
                Craftliltplugin::getInstance()->jobLogsRepository->create(
                    $jobRecord->id,
                    Craft::$app->getUser()->getId(),
                    sprintf(
                        'Job failed after %d attempt(s)',
                        $queueJob->attempt
                    )
                );
            }

            Craftliltplugin::getInstance()->jobLogsRepository->create(
                $jobRecord->id,
                Craft::$app->getUser()->getId(),
                substr(
                    sprintf(
                        'Unexpected error: %s',
                        $event->error->getMessage()
                    ),
                    0,
                    255
                )
            );

            return $event;
        }

        Craft::$app->queue->release(
            (string)$event->id
        );

        LiltLogger::info([
            "message" => sprintf(
                'Released job %s',
                $event->id
            ),
            "queueJob" => $queueJob,
            "attempt" => $queueJob->attempt,
            "liltJobId" => $jobRecord->liltJobId,
            "pluginJobId" => $jobRecord->id,
            "jobRecord" => $jobRecord
        ]);

        $retryJob = $queueJob->getRetryJob();

        $isApiError = $event->error instanceof ApiException
            && $event->error->getCode() === 500;
        $isDeadlockError = $event->error instanceof Exception
            && strpos(
                $event->error->getMessage(),
                'Deadlock found when trying to get lock'
            ) !== false;

        if ($isApiError || $isDeadlockError) {
            // Infrastructure error, we need always retry
            $retryJob->attempt = 0;

            \craft\helpers\Queue::push(
                $retryJob->getRetryJob(),
                $retryJob::PRIORITY,
                $retryJob::getDelay()
            );

            LiltLogger::info([
                "message" => 'Retried job due to infrastructure error',
                "queueJob" => $retryJob,
                "isApiError" => $isApiError,
                "isDeadlockError" => $isDeadlockError,
                "attempt" => $retryJob->attempt,
                "liltJobId" => $jobRecord->liltJobId,
                "pluginJobId" => $jobRecord->id,
                "jobRecord" => $jobRecord
            ]);

            return $event;
        }

        \craft\helpers\Queue::push(
            $retryJob,
            $retryJob::PRIORITY,
            $retryJob::getDelay()
        );

        LiltLogger::info([
            "message" => sprintf(
                'Retried job, attempt %d',
                $queueJob->attempt
            ),
            "queueJob" => $retryJob,
            "attempt" => $retryJob->attempt,
            "liltJobId" => $jobRecord->liltJobId,
            "pluginJobId" => $jobRecord->id,
            "jobRecord" => $jobRecord
        ]);

        return $event;
    }
}
