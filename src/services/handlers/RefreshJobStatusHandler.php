<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\models\TranslationModel;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;

class RefreshJobStatusHandler
{
    public function __invoke(int $jobId): void
    {
        $jobRecord = JobRecord::findOne(['id' => $jobId]);

        if (!$jobRecord) {
            return;
        }

        $translations = Craftliltplugin::getInstance()->translationRepository->findByJobId($jobId);

        $uniqueStatuses = array_unique(
            array_map(static function (TranslationModel $translationRecord) {
                return $translationRecord->status;
            }, $translations)
        );

        if ($uniqueStatuses === [TranslationRecord::STATUS_PUBLISHED]) {
            $jobRecord->status = Job::STATUS_COMPLETE;
            $jobRecord->save();

            Craftliltplugin::getInstance()->jobLogsRepository->create(
                $jobRecord->id,
                Craft::$app->getUser()->getId(),
                'Job published'
            );
        }

        if ($uniqueStatuses === [TranslationRecord::STATUS_READY_TO_PUBLISH]) {
            $jobRecord->status = Job::STATUS_READY_TO_PUBLISH;
            $jobRecord->save();

            Craftliltplugin::getInstance()->jobLogsRepository->create(
                $jobRecord->id,
                Craft::$app->getUser()->getId(),
                'Job reviewed'
            );
        }

        Craft::$app->elements->invalidateCachesForElementType(
            Job::class
        );
    }
}
