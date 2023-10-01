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
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\handlers\commands\CreateDraftCommand;
use lilthq\craftliltplugin\services\mappers\LanguageMapper;
use lilthq\craftliltplugin\services\providers\ElementTranslatableContentProvider;
use lilthq\craftliltplugin\services\repositories\external\ConnectorFileRepository;
use lilthq\craftliltplugin\services\repositories\external\ConnectorJobRepository;
use lilthq\craftliltplugin\services\repositories\JobLogsRepository;
use lilthq\craftliltplugin\services\repositories\TranslationRepository;
use Throwable;
use yii\base\Exception;
use yii\db\StaleObjectException;

class SendJobToLiltConnectorHandler
{
    /**
     * @var ConnectorJobRepository
     */
    public $connectorJobRepository;

    /**
     * @var JobLogsRepository
     */
    public $jobLogsRepository;

    /**
     * @var TranslationRepository
     */
    public $translationRepository;

    /**
     * @var ConnectorFileRepository
     */
    public $connectorJobsFileRepository;

    /**
     * @var CreateDraftHandler
     */
    public $createDraftHandler;

    /**
     * @var ElementTranslatableContentProvider
     */
    public $elementTranslatableContentProvider;

    /**
     * @var LanguageMapper
     */
    public $languageMapper;

    /**
     * @param ConnectorJobRepository $connectorJobRepository
     * @param JobLogsRepository $jobLogsRepository
     * @param TranslationRepository $translationRepository
     * @param ConnectorFileRepository $connectorJobsFileRepository
     * @param CreateDraftHandler $createDraftHandler
     * @param ElementTranslatableContentProvider $elementTranslatableContentProvider
     * @param LanguageMapper $languageMapper
     */
    public function __construct(
        ConnectorJobRepository $connectorJobRepository,
        JobLogsRepository $jobLogsRepository,
        TranslationRepository $translationRepository,
        ConnectorFileRepository $connectorJobsFileRepository,
        CreateDraftHandler $createDraftHandler,
        ElementTranslatableContentProvider $elementTranslatableContentProvider,
        LanguageMapper $languageMapper
    ) {
        $this->connectorJobRepository = $connectorJobRepository;
        $this->jobLogsRepository = $jobLogsRepository;
        $this->translationRepository = $translationRepository;
        $this->connectorJobsFileRepository = $connectorJobsFileRepository;
        $this->createDraftHandler = $createDraftHandler;
        $this->elementTranslatableContentProvider = $elementTranslatableContentProvider;
        $this->languageMapper = $languageMapper;
    }


    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws ApiException
     * @throws Exception
     * @throws StaleObjectException
     */
    public function __invoke(Job $job): void
    {
        $jobLilt = $this->connectorJobRepository->create(
            $job->title,
            strtoupper($job->translationWorkflow)
        );

        $this->jobLogsRepository->create(
            $job->id,
            Craft::$app->getUser()->getId(),
            sprintf('Lilt job created (id: %d)', $jobLilt->getId())
        );

        $elementIdsToTranslate = $job->getElementIds();
        $translations = $this->translationRepository->findRecordsByJobId($job->id);
        /**
         * @var TranslationRecord[][] $translationsMapped
         */
        $translationsMapped = [];

        foreach ($translations as $translation) {
            $translationsMapped[$translation->versionId][$translation->targetSiteId] = $translation;
        }

        foreach ($elementIdsToTranslate as $elementId) {
            $versionId = $job->getElementVersionId($elementId);
            $element = Craft::$app->elements->getElementById($versionId, null, $job->sourceSiteId);

            if (!$element) {
                //TODO: handle
                continue;
            }

            foreach ($job->getTargetSiteIds() as $targetSiteId) {
                //Create draft with & update all values to source element
                $draft = $this->createDraftHandler->create(
                    new CreateDraftCommand(
                        $element,
                        $job->title,
                        $job->sourceSiteId,
                        $targetSiteId,
                        $job->translationWorkflow,
                        $job->authorId
                    )
                );

                $content = $this->elementTranslatableContentProvider->provide(
                    $draft
                );

                $slug = !empty($element->slug) ? $element->slug : 'no-slug-available';

                $result = $this->createJobFile(
                    $content,
                    $versionId,
                    $jobLilt->getId(),
                    $this->languageMapper->getLanguageBySiteId((int)$job->sourceSiteId),
                    $this->languageMapper->getLanguagesBySiteIds(
                        [$targetSiteId]
                    ),
                    null, //TODO: $job->dueDate is not in use
                    $slug
                );

                if (!$result) {
                    $this->updateJob($job, $jobLilt->getId(), Job::STATUS_FAILED);

                    throw new \RuntimeException('Translations not created, upload failed');
                }

                $translation = $translationsMapped[$versionId][$targetSiteId] ?? null;
                if ($translation === null) {
                    $translation = $this->translationRepository->create(
                        $job->id,
                        $elementId,
                        $versionId,
                        $job->sourceSiteId,
                        $targetSiteId,
                        TranslationRecord::STATUS_IN_PROGRESS
                    );
                }

                $translation->sourceContent = $content;
                $translation->translatedDraftId = $draft->id;
                $translation->markAttributeDirty('sourceContent');
                $translation->markAttributeDirty('translatedDraftId');

                if (!$translation->save()) {
                    $this->updateJob($job, $jobLilt->getId(), Job::STATUS_FAILED);

                    throw new \RuntimeException('Translations not created, upload failed');
                }
            }
        }

        $this->updateJob($job, $jobLilt->getId(), Job::STATUS_IN_PROGRESS);

        $this->connectorJobRepository->start($jobLilt->getId());

        $this->jobLogsRepository->create(
            $job->id,
            Craft::$app->getUser()->getId(),
            'Job uploaded to Lilt Platform'
        );

        Queue::push(
            (new FetchJobStatusFromConnector([
                'jobId' => $job->id,
                'liltJobId' => $jobLilt->getId(),
            ])),
            FetchJobStatusFromConnector::PRIORITY,
            10 //10 seconds for fist job
        );
    }

    private function createJobFile(
        array $content,
        int $entryId,
        int $jobId,
        string $sourceLanguage,
        array $targetSiteLanguages,
        ?DateTimeInterface $dueDate,
        string $slug
    ): bool {
        $contentString = json_encode($content);

        if (!empty($slug)) {
            $slug = substr($slug, 0, 150);
        }

        return $this->connectorJobsFileRepository->addFileToJob(
            $jobId,
            'element_' . $entryId . '_' . $slug . '.json+html',
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
