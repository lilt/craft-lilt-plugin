<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\listeners;

use Craft;
use craft\queue\Queue;
use lilthq\craftliltplugin\modules\FetchInstantJobTranslationsFromConnector;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\modules\FetchTranslationFromConnector;
use lilthq\craftliltplugin\modules\FetchVerifiedJobTranslationsFromConnector;
use lilthq\craftliltplugin\modules\SendJobToConnector;
use lilthq\craftliltplugin\modules\SendTranslationToConnector;
use yii\base\Event;
use yii\queue\ExecEvent;
use yii\queue\PushEvent;

class QueueBeforePushListener implements ListenerInterface
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
            Queue::EVENT_BEFORE_PUSH,
            [$this, '__invoke']
        );
    }

    private function isEventEligible(Event $event): bool
    {
        if (!$event instanceof PushEvent) {
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
         * @var FetchTranslationFromConnector|FetchJobStatusFromConnector|SendJobToConnector $newQueueJob
         */
        $newQueueJob = $event->job;

        $jobsInfo = Craft::$app->getQueue()->getJobInfo();

        // Release all previously queued jobs for lilt plugin jobs
        foreach ($jobsInfo as $jobInfo) {
            $jobDetails = Craft::$app->getQueue()->getJobDetails((string)$jobInfo['id']);

            if (!in_array(get_class($jobDetails['job']), self::SUPPORTED_JOBS)) {
                continue;
            }

            if ($jobDetails['status'] === Queue::STATUS_RESERVED) {
                // Job in progress, we can't count it
                continue;
            }

            /**
             * @var FetchTranslationFromConnector|FetchJobStatusFromConnector|SendJobToConnector $newQueueJob
             */
            $existingQueueJob = $jobDetails['job'];

            if (property_exists($newQueueJob, 'translationId')) {
                // if job to be pushed having translation id, only check on jobs with translation id
                if (property_exists($existingQueueJob, 'translationId')) {
                    // compare if job exist for this job id and translation id
                    if (
                        $newQueueJob->jobId === $existingQueueJob->jobId
                        && $newQueueJob->translationId === $existingQueueJob->translationId
                    ) {
                        // we already have this job in process, skipping push
                        $event->handled = true;
                        return $event;
                    }

                    continue;
                }

                continue;
            }

            // compare if job exist for this job id
            if ($newQueueJob->jobId === $existingQueueJob->jobId) {
                // we already have this job in process, skipping push
                $event->handled = true;
                return $event;
            }
        }


        return $event;
    }
}
