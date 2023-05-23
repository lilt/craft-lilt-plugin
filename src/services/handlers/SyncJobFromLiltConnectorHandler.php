<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\errors\InvalidFieldException;
use Exception;
use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\datetime\DateTime;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationNotificationsRecord;
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
            (int)$job->liltJobId
        );

        if ($jobLilt->getStatus() !== JobResponse::STATUS_COMPLETE) {
            return;
        }

        $jobRecord = JobRecord::findOne(['id' => $job->id]);

        if (!$jobRecord) {
            return;
        }

        $translations = Craftliltplugin::getInstance()->connectorTranslationRepository->findByJobId(
            (int)$job->liltJobId
        );

        $unprocessedTranslations = Craftliltplugin::getInstance()
            ->translationRepository
            ->findUnprocessedByJobIdMapped($job->id);

        if (!empty($unprocessedTranslations) || true) {
            foreach ($translations->getResults() as $translationDto) {
                if (
                    $translationDto->getStatus() !== TranslationResponse::STATUS_EXPORT_COMPLETE
                    && $translationDto->getStatus() !== TranslationResponse::STATUS_MT_COMPLETE
                ) {
                    continue;
                }

                try {
                    $this->processTranslation($translationDto, $job);
                } catch (Exception $ex) {
                    Craft::error([
                        'message' => "Can't process translation!",
                        'exception_message' => $ex->getMessage(),
                        'exception_trace' => $ex->getTrace(),
                        'exception' => $ex,
                    ]);

                    Craftliltplugin::getInstance()->translationFailedHandler->__invoke(
                        $translationDto,
                        $job,
                        $unprocessedTranslations
                    );
                }
            }
        }

        Craftliltplugin::getInstance()->updateJobStatusHandler->update($job->id);
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

        $targetLanguage = $this->getTargetLanguage($translationResponse);

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

            $translationRecord = TranslationRecord::findOne([
                'targetSiteId' => Craftliltplugin::getInstance()
                    ->languageMapper
                    ->getSiteIdByLanguage(
                        trim($targetLanguage, '-')
                    ),
                'elementId' => $element->getCanonicalId() ?? $elementId,
                'jobId' => $job->getId()
            ]);

            if (!$translationRecord) {
                Craft::error(
                    sprintf(
                        'Translation record of jobId: %d not found, looks like job was removed.'
                        . ' Translation fetching aborted.',
                        $job->getId()
                    )
                );
            }

            TranslationNotificationsRecord::deleteAll(['translationId' => $translationRecord->id]);

            $translationApplyCommand = new TranslationApplyCommand(
                $element,
                $job,
                $elementContent,
                $targetLanguage,
                $translationRecord
            );

            $draft = Craftliltplugin::getInstance()->elementTranslatableContentApplier->apply(
                $translationApplyCommand
            );

            $translationRecord->translatedDraftId = $draft->getId();

            if ($translationRecord->status !== TranslationRecord::STATUS_NEEDS_ATTENTION) {
                $translationRecord->status = TranslationRecord::STATUS_READY_FOR_REVIEW;
            }

            $translationRecord->targetContent = [$elementId => $elementContent];
            $translationRecord->connectorTranslationId = $translationId;
            $translationRecord->lastDelivery = new DateTime();

            $translationRecord->save();
        }
    }

    private function getTargetLanguage(TranslationResponse $translationResponse): string
    {
        if (empty($translationResponse->getTrgLocale())) {
            return $translationResponse->getTrgLang();
        }

        return sprintf(
            '%s-%s',
            $translationResponse->getTrgLang(),
            $translationResponse->getTrgLocale()
        );
    }
}
