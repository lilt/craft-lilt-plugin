<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\job\lilt;

use Craft;
use craft\errors\InvalidFieldException;
use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
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
            (int) $job->liltJobId
        );

        $result = [];

        if ($jobLilt->getStatus() === JobResponse::STATUS_COMPLETE) {
            $translations = Craftliltplugin::getInstance()->connectorTranslationRepository->findByJobId(
                (int)$job->liltJobId
            );

            foreach ($translations->getResults() as $translationDto) {
                $content = Craftliltplugin::getInstance()->connectorTranslationRepository->findTranslationContentById(
                    $translationDto->getId()
                );

                $result[$translationDto->getName()]['targetLanguage'] = sprintf(
                    '%s-%s',
                    $translationDto->getTrgLang(),
                    $translationDto->getTrgLocale()
                );
                $result[$translationDto->getName()]['content'] = json_decode($content, true);
            }

            //apply the text
            foreach ($result as $fileName => $translatedItem) {
                $targetLanguage = $translatedItem['targetLanguage'];

                foreach ($translatedItem['content'] as $elementId => $contentDto) {

                    $element = Craft::$app->elements->getElementById(
                        (int)$elementId,
                        null,
                        Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage($targetLanguage)
                    );

                    if (!$element) {
                        //TODO: handle
                        continue;
                    }

                    Craftliltplugin::getInstance()->elementTranslatableContentApplier->apply(
                        $element,
                        $job,
                        $contentDto,
                        $targetLanguage
                    );
                }
            }
            return;
        }
    }
}
