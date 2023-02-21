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
use lilthq\craftliltplugin\elements\Translation;
use lilthq\craftliltplugin\modules\FetchInstantJobTranslationsFromConnector;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\modules\FetchTranslationFromConnector;
use lilthq\craftliltplugin\modules\FetchVerifiedJobTranslationsFromConnector;
use lilthq\craftliltplugin\modules\SendJobToConnector;
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

        Craftliltplugin::getInstance()->jobLogsRepository->create(
            $jobRecord->id,
            Craft::$app->getUser()->getId(),
            substr(
                sprintf(
                    'Job failed after %d attempt(s). Error message: %s',
                    $queueJob->attempt,
                    $event->error->getMessage()
                ),
                0,
                255
            )
        );

        if (!$queueJob->canRetry()) {
            $jobRecord->status = Job::STATUS_FAILED;

            $jobRecord->save();

            TranslationRecord::updateAll(
                ['status' => TranslationRecord::STATUS_FAILED],
                ['jobId' => $jobRecord->id]
            );

            Craft::$app->elements->invalidateCachesForElementType(Translation::class);
            Craft::$app->elements->invalidateCachesForElementType(Job::class);

            Craft::$app->queue->release(
                (string) $event->id
            );

            return $event;
        }

        Craft::$app->queue->release(
            (string) $event->id
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
