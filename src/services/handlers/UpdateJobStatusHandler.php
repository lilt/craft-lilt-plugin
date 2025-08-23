<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\elements\Translation;
use lilthq\craftliltplugin\models\TranslationModel;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use RuntimeException;

class UpdateJobStatusHandler
{
    public function update(int $jobId): void
    {
        $translationRecords = Craftliltplugin::getInstance()
            ->translationRepository
            ->findByJobId($jobId);

        $statuses = array_map(static function (TranslationModel $tr) {
            return $tr->status;
        }, $translationRecords);

        $jobRecord = JobRecord::findOne(['id' => $jobId]);

        if (!$jobRecord) {
            Craftliltplugin::getInstance()->jobLogsRepository->create(
                $jobRecord->id,
                Craft::$app->getUser()->getId(),
                "Can't download translations: job record not found"
            );

            throw new RuntimeException('Job record not found');
        }

        if (in_array(TranslationRecord::STATUS_FAILED, $statuses, true)) {
            $jobRecord->status = Job::STATUS_FAILED;
            TranslationRecord::updateAll(
                ['status' => TranslationRecord::STATUS_FAILED],
                ['jobId' => $jobRecord->id]
            );

            $jobRecord->save();

            Craft::error([
                "message" =>  sprintf(
                    'Set job %d and translations to status failed',
                    $jobRecord->id
                ),
                "jobRecord" => $jobRecord,
            ], 'lilt');
        } elseif (in_array(TranslationRecord::STATUS_IN_PROGRESS, $statuses, true)) {
            $jobRecord->status = Job::STATUS_IN_PROGRESS;
            $jobRecord->save();
        } elseif (in_array(TranslationRecord::STATUS_NEEDS_ATTENTION, $statuses, true)) {
            $jobRecord->status = Job::STATUS_NEEDS_ATTENTION;
            $jobRecord->save();

            Craft::warning([
                "message" =>  sprintf(
                    'Set job %d and translations to status needs attention',
                    $jobRecord->id
                ),
                "jobRecord" => $jobRecord,
            ], 'lilt');
        } else {
            $jobRecord->status = Job::STATUS_READY_FOR_REVIEW;
            $jobRecord->save();

            Craft::$app->elements->invalidateCachesForElementType(Translation::class);

            Craftliltplugin::getInstance()->jobLogsRepository->create(
                $jobRecord->id,
                Craft::$app->getUser()->getId(),
                'Translations downloaded'
            );
        }

        Craft::$app->elements->invalidateCachesForElementType(Translation::class);
        Craft::$app->elements->invalidateCachesForElement(
            Job::findOne(['id' => $jobId])
        );
    }
}
