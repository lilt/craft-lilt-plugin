<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\base\ElementInterface;
use craft\errors\ElementNotFoundException;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\elements\Translation;
use lilthq\craftliltplugin\records\TranslationRecord;
use yii\base\Exception;

class CreateTranslationsHandler
{
    /**
     * @param Job $job
     * @param array $sourceContents
     * @param int $elementId
     * @param int $versionId
     * @param ElementInterface[] $drafts
     * @return bool
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function __invoke(
        Job $job,
        array $sourceContents,
        int $elementId,
        int $versionId,
        array $drafts
    ): bool {
        $translationRecords = [];

        foreach ($job->getTargetSiteIds() as $targetSiteId) {
            $config = [
                'jobId' => $job->id,
                'elementId' => $elementId,
                'versionId' => $versionId,
                'sourceSiteId' => $job->sourceSiteId,
                'targetSiteId' => $targetSiteId,
                'sourceContent' => $sourceContents[$targetSiteId] ?? null,
                'status' => TranslationRecord::STATUS_IN_PROGRESS,
                'translatedDraftId' => $drafts[$targetSiteId]->getId()
            ];

            $translation = new Translation($config);
            Craft::$app->getElements()->saveElement($translation);

            $translationRecords[] = new TranslationRecord(
                array_merge(
                    [
                        'id' => $translation->id,
                        'uid' => $translation->uid
                    ],
                    $config
                )
            );
        }

        if (!$translationRecords) {
            return false;
        }

        //DELETE Previous translations if exits
        TranslationRecord::deleteAll([
            'jobId' => $job->id,
            'elementId' => $elementId,
            'versionId' => $versionId,
            'sourceSiteId' => $job->sourceSiteId,
            'targetSiteId' => $job->getTargetSiteIds(),
        ]);

        $result = true;
        foreach ($translationRecords as $translationRecord) {
            assert($translationRecord instanceof TranslationRecord);

            $result = $result && $translationRecord->save();
        }

        return $result;
    }
}
