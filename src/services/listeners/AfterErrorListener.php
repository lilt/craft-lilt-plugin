<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\listeners;

use Craft;
use craft\queue\Queue;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchInstantJobTranslationsFromConnector;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\modules\FetchTranslationFromConnector;
use lilthq\craftliltplugin\modules\FetchVerifiedJobTranslationsFromConnector;
use lilthq\craftliltplugin\modules\SendJobToConnector;
use lilthq\craftliltplugin\modules\SendTranslationToConnector;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use yii\base\Event;
use yii\queue\ExecEvent;

class AfterErrorListener implements ListenerInterface
{
    private const SUPPORTED_JOBS = [
        FetchJobStatusFromConnector::class,
        FetchInstantJobTranslationsFromConnector::class,
        FetchVerifiedJobTranslationsFromConnector::class,
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
         * @var FetchTranslationFromConnector|FetchJobStatusFromConnector|SendJobToConnector $queueJob
         */
        $queueJob = $event->job;

        $jobRecord = JobRecord::findOne(['id' => $queueJob->jobId]);

        if (!$queueJob->canRetry()) {
            $jobRecord->status = Job::STATUS_FAILED;
            $jobRecord->save();

            TranslationRecord::updateAll(
                ['status' => TranslationRecord::STATUS_FAILED],
                ['jobId' => $jobRecord->id]
            );

            Craft::$app->elements->invalidateCachesForElementType(TranslationRecord::class);
            Craft::$app->elements->invalidateCachesForElementType(Job::class);

            Craft::$app->queue->release(
                (string)$event->id
            );

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

        Craft::error(
            sprintf(
                'Job %s %s failed due to: %s',
                get_class($queueJob),
                $queueJob->getDescription(),
                $event->error->getMessage()
            )
        );

        Craft::$app->queue->release(
            (string)$event->id
        );

        ++$queueJob->attempt;

        \craft\helpers\Queue::push(
            $queueJob,
            $queueJob::PRIORITY,
            $queueJob::getDelay()
        );

        return $event;
    }
}
