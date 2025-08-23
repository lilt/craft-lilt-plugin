<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2024 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\helpers\Queue;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\elements\Translation;
use lilthq\craftliltplugin\modules\SendJobToConnector;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;

/**
 * Class to set job attributes to state before sending it for translation
 * and publish queue message to send job to connector
 */
class ResendJobHandler
{
    /**
     * @throws \Throwable
     */
    public function __invoke(int $jobId): void
    {
        // Set job status to in progress and lilt job id to null
        $jobRecord = JobRecord::findOne(['id' => $jobId]);
        $jobRecord->status = Job::STATUS_IN_PROGRESS;
        $jobRecord->liltJobId = null;
        $jobRecord->save();

        // Remove all drafts related to job
        $translationRecords = TranslationRecord::findAll(['jobId' => $jobId]);
        array_map(static function (TranslationRecord $t) {
            if ($t->translatedDraftId !== null) {
                Craft::$app->elements->deleteElementById(
                    $t->translatedDraftId
                );
            }
        }, $translationRecords);


        // Set translation status to in progress and connector translation id to null
        TranslationRecord::updateAll([
            'status' => TranslationRecord::STATUS_IN_PROGRESS,
            'connectorTranslationId' => null,
            'translatedDraftId' => null,
            'sourceContent' => null
        ], ['jobId' => $jobId]);

        // Invalidate caches for job and translation types
        Craft::$app->getElements()->invalidateCachesForElementType(Job::class);
        Craft::$app->getElements()->invalidateCachesForElementType(Translation::class);

        // Trigger send job to connector
        Queue::push(
            new SendJobToConnector(['jobId' => $jobId]),
            SendJobToConnector::PRIORITY,
            10
        );
    }
}
