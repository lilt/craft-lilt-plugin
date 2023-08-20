<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\errors\ElementNotFoundException;
use LiltConnectorSDK\ApiException;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\elements\Translation;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\handlers\commands\CreateDraftCommand;
use Throwable;
use yii\base\Exception;
use yii\db\StaleObjectException;

class CopySourceTextHandler
{
    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws ApiException
     * @throws Exception
     * @throws StaleObjectException
     */
    public function __invoke(Job $job): void
    {
        $elementIdsToTranslate = $job->getElementIds();

        foreach ($elementIdsToTranslate as $elementId) {
            $versionId = $job->getElementVersionId($elementId);
            $element = Craft::$app->elements->getElementById($versionId, null, $job->sourceSiteId);

            if (!$element) {
                //TODO: handle
                continue;
            }
            $drafts = [];
            $contents = [];
            foreach ($job->getTargetSiteIds() as $targetSiteId) {
                //Create draft with & update all values to source element
                $drafts[$targetSiteId] = Craftliltplugin::getInstance()->createDraftHandler->create(
                    new CreateDraftCommand(
                        $element,
                        $job->title,
                        $job->sourceSiteId,
                        $targetSiteId,
                        $job->translationWorkflow
                    )
                );

                $contents[$targetSiteId] = Craftliltplugin::getInstance()->elementTranslatableContentProvider->provide(
                    $drafts[$targetSiteId]
                );
            }

            $createTranslationsResult = Craftliltplugin::getInstance()->createTranslationsHandler->__invoke(
                $job,
                $contents,
                $elementId,
                $versionId,
                $drafts
            );

            if (!$createTranslationsResult) {
                $this->updateJob($job, null, Job::STATUS_FAILED);

                throw new \RuntimeException('Translations not created, upload failed');
            }

            $translations = TranslationRecord::findAll(['jobId' => $job->getId(), 'elementId' => $elementId]);
            $success = true;
            foreach ($translations as $translation) {
                $translation->status = TranslationRecord::STATUS_READY_FOR_REVIEW;
                $translation->targetContent = $contents[$translation->targetSiteId];

                $success = $success && $translation->save();
            }

            if (!$success) {
                $this->updateJob($job, null, Job::STATUS_FAILED);

                Craftliltplugin::getInstance()->jobLogsRepository->create(
                    $job->id,
                    Craft::$app->getUser()->getId(),
                    'Failed to copy source text to targets'
                );

                return;
            }
        }

        $this->updateJob($job, null, Job::STATUS_READY_FOR_REVIEW);

        Craftliltplugin::getInstance()->jobLogsRepository->create(
            $job->id,
            Craft::$app->getUser()->getId(),
            'Source text copied to targets'
        );
    }

    /**
     * @param Job $job
     * @param int|null $jobLiltId
     * @param string $status
     * @return void
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws StaleObjectException
     * @throws Throwable
     */
    private function updateJob(Job $job, ?int $jobLiltId, string $status): void
    {
        $job->liltJobId = $jobLiltId;
        $job->status = $status;

        //TODO: check how it works
        Craft::$app->getElements()->saveElement($job, true, true, true);

        $jobRecord = JobRecord::findOne(['id' => $job->id]);

        $jobRecord->status = $status;
        $jobRecord->liltJobId = $jobLiltId;

        $jobRecord->update();

        if ($status === Job::STATUS_FAILED) {
            TranslationRecord::updateAll(
                ['status' => TranslationRecord::STATUS_FAILED],
                ['jobId' => $jobRecord->id]
            );
        }

        Craft::$app->getElements()->invalidateCachesForElementType(Job::class);
        Craft::$app->getElements()->invalidateCachesForElementType(Translation::class);
    }
}
