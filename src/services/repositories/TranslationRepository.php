<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories;

use Craft;
use Throwable;
use craft\errors\ElementNotFoundException;
use lilthq\craftliltplugin\elements\Translation;
use lilthq\craftliltplugin\models\TranslationModel;
use lilthq\craftliltplugin\records\TranslationRecord;
use yii\base\Exception;

class TranslationRepository
{
    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function create(
        int $jobId,
        int $elementId,
        int $versionId,
        int $sourceSiteId,
        int $targetSiteId,
        string $status,
        ?string $sourceContent = null,
        ?int $translatedDraftId = null
    ): TranslationRecord {
        $config = [
            'jobId'             => $jobId,
            'elementId'         => $elementId,
            'versionId'         => $versionId,
            'sourceSiteId'      => $sourceSiteId,
            'targetSiteId'      => $targetSiteId,
            'sourceContent'     => $sourceContent,
            'status'            => $status,
            'translatedDraftId' => $translatedDraftId
        ];

        $translation = new Translation($config);
        Craft::$app->getElements()->saveElement($translation);

        $translationRecord =  new TranslationRecord(
            array_merge(
                [
                    'id' => $translation->id,
                    'uid' => $translation->uid
                ],
                $config
            )
        );

        $translationRecord->save();

        return $translationRecord;
    }

    /**
     * @param int $jobId
     * @return TranslationModel[]
     */
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

    /**
     * @param int $jobId
     * @return TranslationModel[]
     */
    public function findRecordsByJobId(int $jobId): array
    {
        return TranslationRecord::findAll(['jobId' => $jobId]);
    }

    /**
     * @param int $jobId
     * @param int $elementId
     *
     * @return TranslationRecord[]
     */
    public function findByJobIdAndElement(
        int $jobId,
        int $elementId
    ): array {
        return TranslationRecord::findAll([
            'jobId' => $jobId,
            'elementId' => $elementId,
        ]);
    }

    public function findByJobIdSortByStatus(int $jobId): array
    {
        $translationRecords = TranslationRecord::find()
            ->where(['jobId' => $jobId])
            ->all();

        $sort = [
            TranslationRecord::STATUS_READY_FOR_REVIEW => 1,
            TranslationRecord::STATUS_READY_TO_PUBLISH => 2,
            TranslationRecord::STATUS_PUBLISHED => 3,
        ];

        $translations = [];
        foreach ($translationRecords as $translationRecord) {
            $translationModel = new TranslationModel(
                $translationRecord->toArray()
            );

            if (isset($sort[$translationModel->status])) {
                $translations[$sort[$translationModel->status]][] = $translationModel;

                continue;
            }

            $translations[0][] = $translationModel;
        }

        ksort($translations);

        return array_merge(...$translations);
    }

    public function findInProgressByElementId(int $elementId): array
    {
        $translationRecords = TranslationRecord::findAll([
            'elementId' => $elementId,
            'status' => [
                TranslationRecord::STATUS_IN_PROGRESS,
                TranslationRecord::STATUS_READY_TO_PUBLISH,
                TranslationRecord::STATUS_READY_FOR_REVIEW,
                TranslationRecord::STATUS_FAILED,
            ]
        ]);

        return array_map(
            static function (TranslationRecord $translationRecord) {
                return new TranslationModel(
                    $translationRecord->toArray()
                );
            },
            $translationRecords
        );
    }

    public function findRecordByTranslatedDraftId(int $translatedDraftId): ?TranslationRecord
    {
        return TranslationRecord::findOne(['translatedDraftId' => $translatedDraftId]);
    }

    public function findUnprocessedByJobIdMapped(int $jobId): array
    {
        $result = TranslationRecord::findAll(
            [
                'jobId' => $jobId,
                'status' => [
                    TranslationRecord::STATUS_IN_PROGRESS,
                    TranslationRecord::STATUS_FAILED,
                ]
            ]
        );

        $mapped = [];
        foreach ($result as $item) {
            $mapped[$item->elementId][$item->targetSiteId] = $item;
        }

        return $mapped;
    }

    public function findOneById(int $id): ?TranslationModel
    {
        $translationRecord = TranslationRecord::findOne(['id' => $id]);

        if (!$translationRecord) {
            return null;
        }

        return new TranslationModel(
            $translationRecord->toArray()
        );
    }
}
