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
use LiltConnectorSDK\ApiException;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\modules\SendTranslationToConnector;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\handlers\commands\SendTranslationCommand;
use lilthq\craftliltplugin\services\mappers\LanguageMapper;
use lilthq\craftliltplugin\services\repositories\external\ConnectorJobRepository;
use lilthq\craftliltplugin\services\repositories\JobLogsRepository;
use lilthq\craftliltplugin\services\repositories\SettingsRepository;
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
     * @var LanguageMapper
     */
    public $languageMapper;

    /**
     * @var SendTranslationToLiltConnectorHandler
     */
    public $sendTranslationToLiltConnectorHandler;

    /**
     * @var SettingsRepository
     */
    public $settingsRepository;

    /**
     * @param ConnectorJobRepository $connectorJobRepository
     * @param JobLogsRepository $jobLogsRepository
     * @param TranslationRepository $translationRepository
     * @param LanguageMapper $languageMapper
     * @param SendTranslationToLiltConnectorHandler $sendTranslationToLiltConnectorHandler
     * @param SettingsRepository $settingsRepository
     */
    public function __construct(
        ConnectorJobRepository $connectorJobRepository,
        JobLogsRepository $jobLogsRepository,
        TranslationRepository $translationRepository,
        LanguageMapper $languageMapper,
        SendTranslationToLiltConnectorHandler $sendTranslationToLiltConnectorHandler,
        SettingsRepository $settingsRepository
    ) {
        $this->connectorJobRepository = $connectorJobRepository;
        $this->jobLogsRepository = $jobLogsRepository;
        $this->translationRepository = $translationRepository;
        $this->languageMapper = $languageMapper;
        $this->sendTranslationToLiltConnectorHandler = $sendTranslationToLiltConnectorHandler;
        $this->settingsRepository = $settingsRepository;
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
        $isSplitJobFileUploadEnabled = $this->settingsRepository->isSplitJobFileUploadEnabled();

        $jobLilt = $this->connectorJobRepository->create(
            $job->title,
            strtoupper($job->translationWorkflow)
        );

        $this->jobLogsRepository->create(
            $job->id,
            Craft::$app->getUser()->getId(),
            sprintf('Lilt job created (id: %d)', $jobLilt->getId())
        );

        $translationsMapped = $this->getTranslationsMapped($job);

        foreach ($job->getElementIds() as $elementId) {
            $versionId = $job->getElementVersionId($elementId);
            $element = Craft::$app->elements->getElementById($versionId, null, $job->sourceSiteId);

            if (!$element) {
                Craft::error(
                    sprintf("Can't find element: %d for job: %d", $versionId, $job->id)
                );

                continue;
            }

            foreach ($job->getTargetSiteIds() as $targetSiteId) {
                $translation = $translationsMapped[$versionId][$targetSiteId] ?? null;
                if ($isSplitJobFileUploadEnabled) {
                    Queue::push(
                        new SendTranslationToConnector([
                            'jobId' => $job->id,
                            'translationId' => $translation->id ?? null,
                            'elementId' => $elementId,
                            'versionId' => $versionId,
                            'targetSiteId' => $targetSiteId,
                        ]),
                        SendTranslationToConnector::PRIORITY,
                        SendTranslationToConnector::getDelay(),
                        SendTranslationToConnector::TTR
                    );

                    continue;
                }

                $this->sendTranslationToLiltConnectorHandler->send(
                    new SendTranslationCommand(
                        $elementId,
                        $versionId,
                        $targetSiteId,
                        $element,
                        $jobLilt->getId(),
                        $job,
                        $translation
                    )
                );
            }
        }

        $this->updateJob($job, $jobLilt->getId(), Job::STATUS_IN_PROGRESS);

        if ($isSplitJobFileUploadEnabled) {
            return;
        }

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

    /**
     * @param Job $job
     * @return array|TranslationRecord[][]
     */
    private function getTranslationsMapped(Job $job): array
    {
        $translations = $this->translationRepository->findRecordsByJobId($job->id);
        /**
         * @var TranslationRecord[][] $translationsMapped
         */
        $translationsMapped = [];

        foreach ($translations as $translation) {
            $translationsMapped[$translation->versionId][$translation->targetSiteId] = $translation;
        }
        return $translationsMapped;
    }
}
