<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\job\lilt;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\helpers\Queue;
use DateTimeInterface;
use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\records\TranslationRecord;
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

        $translationRecords = [];
        $versions = $job->getVersions();

        foreach ($elementIdsToTranslate as $elementId) {
            if (isset($versions[$elementId]) && $versions[$elementId] === 'null') {
                $versions[$elementId] = null;
            }

            $versionId = (int)($versions[$elementId] ?? $elementId);

            $element = Craft::$app->elements->getElementById($versionId, null, $job->sourceSiteId);

            if (!$element) {
                //TODO: handle
                continue;
            }

            $content = Craftliltplugin::getInstance()->elementTranslatableContentProvider->provide(
                $element
            );

            $result = $this->createJobFile(
                $content,
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

            $translationRecords[] = array_values(
                array_map(
                    static function (int $targetSiteId) use ($job, $content, $elementId, $versionId) {
                        return new TranslationRecord([
                            'jobId' => $job->id,
                            'elementId' => $elementId,
                            'versionId' => $versionId,
                            'sourceSiteId' => $job->sourceSiteId,
                            'targetSiteId' => $targetSiteId,
                            'sourceContent' => $content,
                            'status' => TranslationRecord::STATUS_IN_PROGRESS,
                        ]);
                    },
                    $job->getTargetSiteIds()
                )
            );
        }

        $translationRecords = array_merge(...$translationRecords);

        foreach ($translationRecords as $jobElementRecord) {
            /**
             * @var TranslationRecord $jobElementRecord
             */
            $jobElementRecord->save();
        }

        $job->liltJobId = $jobLilt->getId();
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
     * @param JobResponse $jobLilt
     * @param Job $job
     * @return JobRecord|null
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
