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
use lilthq\craftliltplugin\services\handlers\commands\SendTranslationCommand;
use lilthq\craftliltplugin\services\mappers\LanguageMapper;
use lilthq\craftliltplugin\services\providers\ElementTranslatableContentProvider;
use lilthq\craftliltplugin\services\repositories\external\ConnectorFileRepository;
use lilthq\craftliltplugin\services\repositories\external\ConnectorJobRepository;
use lilthq\craftliltplugin\services\repositories\JobLogsRepository;
use lilthq\craftliltplugin\services\repositories\TranslationRepository;
use Throwable;
use yii\base\Exception;
use yii\db\StaleObjectException;

class SendTranslationToLiltConnectorHandler
{
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
     * @param JobLogsRepository $jobLogsRepository
     * @param TranslationRepository $translationRepository
     * @param ConnectorFileRepository $connectorJobsFileRepository
     * @param CreateDraftHandler $createDraftHandler
     * @param ElementTranslatableContentProvider $elementTranslatableContentProvider
     * @param LanguageMapper $languageMapper
     */
    public function __construct(
        JobLogsRepository $jobLogsRepository,
        TranslationRepository $translationRepository,
        ConnectorFileRepository $connectorJobsFileRepository,
        CreateDraftHandler $createDraftHandler,
        ElementTranslatableContentProvider $elementTranslatableContentProvider,
        LanguageMapper $languageMapper
    ) {
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
    public function send(SendTranslationCommand $sendTranslationCommand): void
    {
        $job = $sendTranslationCommand->getJob();
        $element = $sendTranslationCommand->getElement();
        $versionId = $sendTranslationCommand->getVersionId();
        $elementId = $sendTranslationCommand->getElementId();
        $liltJobId = $sendTranslationCommand->getLiltJobId();
        $translation = $sendTranslationCommand->getTranslationRecord();
        $targetSiteId = $sendTranslationCommand->getTargetSiteId();

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
            $liltJobId,
            $this->languageMapper->getLanguageBySiteId((int)$job->sourceSiteId),
            $this->languageMapper->getLanguagesBySiteIds(
                [$targetSiteId]
            ),
            null, //TODO: $job->dueDate is not in use
            $slug
        );

        if (!$result) {
            $this->updateJob($job, $liltJobId, Job::STATUS_FAILED);

            throw new \RuntimeException('Translations not created, upload failed');
        }

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
            $this->updateJob($job, $liltJobId, Job::STATUS_FAILED);

            throw new \RuntimeException('Translations not created, upload failed');
        }
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
     *
     * @return void
     *
     * @throws Exception
     * @throws StaleObjectException
     * @throws Throwable
     */
    private function updateJob(Job $job, int $jobLiltId, string $status): void
    {
        $jobRecord = JobRecord::findOne(['id' => $job->id]);

        $jobRecord->status = $status;
        $jobRecord->liltJobId = $jobLiltId;

        $jobRecord->update();
        Craft::$app->getCache()->flush();
    }
}
