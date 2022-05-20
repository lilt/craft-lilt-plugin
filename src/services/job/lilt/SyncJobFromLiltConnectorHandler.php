<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\job\lilt;

use Craft;
use craft\errors\InvalidFieldException;
use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\datetime\DateTime;
use lilthq\craftliltplugin\elements\Job;
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
        $jobLilt = Craftliltplugin::getInstance()->connectorJobRepository->findOneById(
            (int)$job->liltJobId
        );

        $elements = $job->getElementsMappedById();
        $translationModels = $job->getTranslations();

        $result = [];

        if ($jobLilt->getStatus() === JobResponse::STATUS_COMPLETE) {
            $translations = Craftliltplugin::getInstance()->connectorTranslationRepository->findByJobId(
                (int)$job->liltJobId
            );

            $values = array_map(static function (TranslationResponse $translationResponse) {
                /* return [
                    'translationStatus' => $translationResponse->getStatus(),
                    'translationId' => $translationResponse->getId(),
                    'trgLang' => $translationResponse->getTrgLang(),
                    'trgLocale' => $translationResponse->getTrgLocale(),
                    'updatedAt' => $translationResponse->getUpdatedAt(),
                ]; */
            }, $translations->getResults());

            foreach ($translations->getResults() as $translationDto) {
                $content = Craftliltplugin::getInstance()->connectorTranslationRepository->findTranslationContentById(
                    $translationDto->getId()
                );

                $result[] = [
                    'translationId' => $translationDto->getId(),
                    'targetLanguage' => sprintf(
                        '%s-%s',
                        $translationDto->getTrgLang(),
                        $translationDto->getTrgLocale()
                    ),
                    'content' => json_decode($content, true)
                ];
            }

            //apply the text
            foreach ($result as $translatedItem) {
                $targetLanguage = $translatedItem['targetLanguage'];
                $translationId = $translatedItem['translationId'];

                foreach ($translatedItem['content'] as $elementId => $contentDto) {
                    $element = Craft::$app->elements->getElementById(
                        (int)$elementId,
                        null,
                        //Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage($targetLanguage)
                        $job->sourceSiteId
                    );

                    if (!$element) {
                        //TODO: handle
                        continue;
                    }

                    $translationApplyCommand = new TranslationApplyCommand(
                        $element,
                        $job,
                        $contentDto,
                        $targetLanguage
                    );

                    $draft = Craftliltplugin::getInstance()->elementTranslatableContentApplier->apply(
                        $translationApplyCommand
                    );

                    $translationRecord = TranslationRecord::findOne([
                        'targetSiteId' => Craftliltplugin::getInstance()
                            ->languageMapper
                            ->getSiteIdByLanguage($targetLanguage),
                        'elementId' => $draft->getCanonicalId() ?? $elementId,
                        'jobId' => $job->getId()
                    ]);
                    $translationRecord->translatedDraftId = $draft->getId();
                    $translationRecord->status = TranslationRecord::STATUS_READY_FOR_REVIEW;
                    $translationRecord->targetContent = [$elementId => $contentDto];
                    $translationRecord->connectorTranslationId = $translationId;
                    $translationRecord->lastDelivery = new DateTime();

                    $translationRecord->save();
                }
            }
            return;
        }
    }
}
