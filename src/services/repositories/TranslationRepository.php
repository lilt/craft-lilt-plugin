<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories;

use lilthq\craftliltplugin\models\TranslationModel;
use lilthq\craftliltplugin\records\TranslationRecord;

class TranslationRepository
{
    public function findByJobId(int $jobId): array
    {
        $translationRecords = TranslationRecord::findAll(['jobId' => $jobId]);

        return array_map(
            static function (TranslationRecord $translationRecord) {
                return new TranslationModel(
                    $translationRecord->toArray()
                );
            },
            $translationRecords
        );
    }

    public function findUnprocessedByJobIdMapped(int $jobId): array
    {
        $result = TranslationRecord::findAll(
            [
                'jobId' => $jobId,
                'status' => [
                    TranslationRecord::STATUS_IN_PROGRESS,
                    TranslationRecord::STATUS_NEW
                ]
            ]
        );

        $mapped = [];
        foreach ($result as $item) {
            $mapped[$item->elementId][$item->targetSiteId] = $item;
        }

        return $mapped;
    }

    public function findOneById(int $id): TranslationModel
    {
        $translationRecord = TranslationRecord::findOne(['id' => $id]);

        return new TranslationModel(
            $translationRecord->toArray()
        );
    }
}
