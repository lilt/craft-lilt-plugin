<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\job\lilt;

use Craft;
use LiltConnectorSDK\Model\JobResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;

class SyncJobFromLiltConnectorHandler
{
    public function __invoke(Job $job): void
    {
        $jobLilt = Craftliltplugin::getInstance()->liltJobRepository->findOneById(
            (int)$job->liltJobId
        );

        $result = [];

        if ($jobLilt->getStatus() === JobResponse::STATUS_COMPLETE) {
            $translations = Craftliltplugin::getInstance()->liltTranslationRepository->findByJobId(
                (int)$job->liltJobId
            );

            foreach ($translations->getResults() as $translationDto) {
                $content = Craftliltplugin::getInstance()->liltTranslationRepository->findTranslationContentById(
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

                foreach ($translatedItem['content'] as $keyString => $elementDto) {
                    [$elementType, $elementId] = explode('.', $keyString);

                    $element = Craft::$app->elements->getElementById(
                        (int)$elementId,
                        $elementType,
                        Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage($targetLanguage)
                    );

                    if (!$element) {
                        //TODO: handle
                        continue;
                    }

                    //TODO: apply changes somehow?
                }
            }
            return;
        }
    }
}