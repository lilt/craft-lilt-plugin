<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\job\lilt;

use Craft;
use craft\errors\InvalidFieldException;
use Exception;
use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\datetime\DateTime;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\models\TranslationModel;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\appliers\TranslationApplyCommand;
use Throwable;

class SyncJobFromLiltConnectorHandler
{
    /**
     * @throws InvalidFieldException
     * @throws ApiException
     * @throws Throwable
     */
    public function __invoke(Job $job): void
    {
        if (empty($job->liltJobId)) {
            return;
        }

        $jobLilt = Craftliltplugin::getInstance()->connectorJobRepository->findOneById(
            (int) $job->liltJobId
        );

        if ($jobLilt->getStatus() !== JobResponse::STATUS_COMPLETE) {
            return;
        }

        $jobRecord = JobRecord::findOne(['id' => $job->id]);
        $translations = Craftliltplugin::getInstance()->connectorTranslationRepository->findByJobId(
            (int)$job->liltJobId
        );

        $unprocessedTranslations = Craftliltplugin::getInstance()
            ->translationRepository
            ->findUnprocessedByJobIdMapped($job->id);

        if (!empty($unprocessedTranslations)) {
            foreach ($translations->getResults() as $translationDto) {
                try {
                    $this->processTranslation($translationDto, $job);
                } catch (Exception $ex) {
                    $translationRecord = $this->handleTranslationRecord(
                        $translationDto,
                        $job,
                        $unprocessedTranslations
                    );

                    $translationRecord->status = TranslationRecord::STATUS_FAILED;
                    $translationRecord->lastDelivery = new DateTime();
                    $translationRecord->save();

                    Craft::error(sprintf('%s %s', $ex->getMessage(), $ex->getTraceAsString()));
                }
            }
        }

        $translationRecords = Craftliltplugin::getInstance()
            ->translationRepository
            ->findByJobId($job->id);

        $statuses = array_map(static function (TranslationModel $tr) {
            return $tr->status;
        }, $translationRecords);

        if (in_array('failed', $statuses, true)) {
            $jobRecord->status = Job::STATUS_FAILED;
            $jobRecord->save();
        } else {
            $jobRecord->status = Job::STATUS_READY_FOR_REVIEW;
            $jobRecord->save();
        }

        Craftliltplugin::getInstance()->jobLogsRepository->create(
            $jobRecord->id,
            Craft::$app->getUser()->getId(),
            'Translations downloaded'
        );

        Craft::$app->elements->invalidateCachesForElement($job);
    }


    private function getElementIdFromFileName(TranslationResponse $translationResponse): int
    {
        $regExpr = '/\d+_element_(\d+).json\+html/';
        preg_match($regExpr, $translationResponse->getName(), $matches);

        if (!isset($matches[1])) {
            throw new \RuntimeException('Cant find element id from translation name');
        }

        return (int)$matches[1];
    }

    /**
     * @param $translationResponse
     * @param $job
     * @param array $unprocessedTranslations
     * @return mixed
     */
    private function handleTranslationRecord($translationResponse, $job, array $unprocessedTranslations)
    {
        $translationTargetLanguage = sprintf(
            '%s-%s',
            $translationResponse->getTrgLang(),
            $translationResponse->getTrgLocale()
        );

        $elementId = $this->getElementIdFromFileName($translationResponse);

        $element = Craft::$app->elements->getElementById(
            $elementId,
            null,
            $job->sourceSiteId
        );

        if (!$element) {
            //TODO: handle when element not found?
        }

        $targetSiteId = Craftliltplugin::getInstance()
            ->languageMapper
            ->getSiteIdByLanguage($translationTargetLanguage);
        $parentElementId = $element->getCanonicalId() ?? $elementId;

        $translationRecord = $unprocessedTranslations[$parentElementId][$targetSiteId];

        if (empty($translationRecord->translatedDraftId)) {
            $translationRecord->translatedDraftId = $element->getId();
        }
        return $translationRecord;
    }

    /**
     * @param $translationResponse
     * @param Job $job
     * @return void
     * @throws ApiException
     * @throws InvalidFieldException
     * @throws Throwable
     */
    public function processTranslation(TranslationResponse $translationResponse, Job $job): void
    {
        $content = Craftliltplugin::getInstance()->connectorTranslationRepository->findTranslationContentById(
            $translationResponse->getId()
        );

        $content = json_decode($content, true);

        $translationId = $translationResponse->getId();

        $targetLanguage = sprintf(
            '%s-%s',
            $translationResponse->getTrgLang(),
            $translationResponse->getTrgLocale()
        );

        foreach ($content as $elementId => $elementContent) {
            $element = Craft::$app->elements->getElementById(
                (int)$elementId,
                null,
                $job->sourceSiteId
            );

            if (!$element) {
                //TODO: handle
                continue;
            }

            $translationApplyCommand = new TranslationApplyCommand(
                $element,
                $job,
                $elementContent,
                $targetLanguage
            );

            $draft = Craftliltplugin::getInstance()->elementTranslatableContentApplier->apply(
                $translationApplyCommand
            );

            //TODO: move to repository or so
            $translationRecord = TranslationRecord::findOne([
                'targetSiteId' => Craftliltplugin::getInstance()
                    ->languageMapper
                    ->getSiteIdByLanguage($targetLanguage),
                'elementId' => $draft->getCanonicalId() ?? $elementId,
                'jobId' => $job->getId()
            ]);

            $translationRecord->translatedDraftId = $draft->getId();
            $translationRecord->status = TranslationRecord::STATUS_READY_FOR_REVIEW;
            $translationRecord->targetContent = [$elementId => $elementContent];
            $translationRecord->connectorTranslationId = $translationId;
            $translationRecord->lastDelivery = new DateTime();

            $translationRecord->save();
        }
    }
}
