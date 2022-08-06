<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\helpers\Queue;
use DateTimeInterface;
use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\records\JobRecord;
use Throwable;
use yii\base\Exception;
use yii\db\StaleObjectException;

class SendJobToLiltConnectorHandler
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
        $jobLilt = Craftliltplugin::getInstance()->connectorJobRepository->create(
            $job->title,
            strtoupper($job->translationWorkflow)
        );

        Craftliltplugin::getInstance()->jobLogsRepository->create(
            $job->id,
            Craft::$app->getUser()->getId(),
            sprintf('Lilt job created (id: %d)', $jobLilt->getId())
        );

        $elementIdsToTranslate = $job->getElementIds();

        $targetLanguages = Craftliltplugin::getInstance()->languageMapper->getLanguagesBySiteIds(
            $job->getTargetSiteIds()
        );

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
                    $element,
                    $job->title,
                    (int) $job->sourceSiteId,
                    (int) $targetSiteId
                );

                $contents[$targetSiteId] = Craftliltplugin::getInstance()->elementTranslatableContentProvider->provide(
                    $drafts[$targetSiteId]
                );

                $result = $this->createJobFile(
                    $contents[$targetSiteId],
                    $versionId,
                    $jobLilt->getId(),
                    Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId((int)$job->sourceSiteId),
                    $targetLanguages,
                    null //TODO: $job->dueDate is not in use
                );

                if (!$result) {
                    //TODO: set job failed and exit
                    $this->updateJob($job, $jobLilt->getId(), Job::STATUS_FAILED);
                    return;
                }
            }

            $createTranslationsResult = Craftliltplugin::getInstance()->createTranslationsHandler->__invoke(
                $job,
                $contents,
                $elementId,
                $versionId,
                $drafts
            );

            if (!$createTranslationsResult) {
                $this->updateJob($job, $jobLilt->getId(), Job::STATUS_FAILED);

                throw new \RuntimeException('Translations not created, upload failed');
            }
        }

        $this->updateJob($job, $jobLilt->getId(), Job::STATUS_IN_PROGRESS);

        Craftliltplugin::getInstance()->connectorJobRepository->start($jobLilt->getId());

        Craftliltplugin::getInstance()->jobLogsRepository->create(
            $job->id,
            Craft::$app->getUser()->getId(),
            'Job uploaded to Lilt Platform'
        );

        Queue::push(
            (new FetchJobStatusFromConnector([
                'jobId' => $job->id,
                'liltJobId' => $jobLilt->getId(),
            ]))
        );
    }

    private function createJobFile(
        array $content,
        int $entryId,
        int $jobId,
        string $sourceLanguage,
        array $targetSiteLanguages,
        ?DateTimeInterface $dueDate
    ): bool {
        $contentString = json_encode($content);

        return Craftliltplugin::getInstance()->connectorJobsFileRepository->addFileToJob(
            $jobId,
            'element_' . $entryId . '.json+html',
            $contentString,
            $sourceLanguage,
            $targetSiteLanguages,
            $dueDate
        );
    }

    /**
     * @param Job $job
     * @param int $jobLiltId
     * @param string $status
     * @return void
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws StaleObjectException
     * @throws Throwable
     */
    private function updateJob(Job $job, int $jobLiltId, string $status): void
    {
        $job->liltJobId = $jobLiltId;
        $job->status = $status;

        //TODO: check how it works
        Craft::$app->getElements()->saveElement($job, true, true, true);

        $jobRecord = JobRecord::findOne(['id' => $job->id]);

        $jobRecord->status = $status;
        $jobRecord->liltJobId = $jobLiltId;

        $jobRecord->update();
        Craft::$app->getCache()->flush();
    }
}
