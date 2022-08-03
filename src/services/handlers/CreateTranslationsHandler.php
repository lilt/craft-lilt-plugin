<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use craft\base\ElementInterface;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\TranslationRecord;

class CreateTranslationsHandler
{
    /**
     * @param Job $job
     * @param array $sourceContent
     * @param int $elementId
     * @param int $versionId
     * @param ElementInterface[] $drafts
     * @return bool
     */
    public function __invoke(
        Job $job,
        array $sourceContent,
        int $elementId,
        int $versionId,
        array $drafts
    ): bool {
        $translationRecords = array_values(
            array_map(
                static function (int $targetSiteId) use ($job, $sourceContent, $elementId, $versionId, $drafts) {
                    return new TranslationRecord([
                        'jobId' => $job->id,
                        'elementId' => $elementId,
                        'versionId' => $versionId,
                        'sourceSiteId' => $job->sourceSiteId,
                        'targetSiteId' => $targetSiteId,
                        'sourceContent' => $sourceContent,
                        'status' => TranslationRecord::STATUS_IN_PROGRESS,
                        'translatedDraftId' => $drafts[$targetSiteId]->getId()
                    ]);
                },
                $job->getTargetSiteIds()
            )
        );

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
