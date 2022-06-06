<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\errors\ElementNotFoundException;
use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\TranslationRecord;

class TranslationFailedHandler
{
    /**
     * @throws ElementNotFoundException
     */
    public function __invoke(
        TranslationResponse $translationResponse,
        Job $job,
        array $unprocessedTranslations
    ): TranslationRecord {
        $translationTargetLanguage = sprintf(
            '%s-%s',
            $translationResponse->getTrgLang(),
            $translationResponse->getTrgLocale()
        );

        $elementId = Craftliltplugin::getInstance()
            ->connectorTranslationRepository
            ->getElementIdFromTranslationResponse($translationResponse);

        $element = Craft::$app->elements->getElementById(
            $elementId,
            null,
            $job->sourceSiteId
        );

        if (!$element) {
            //TODO: handle when element not found?
            throw new ElementNotFoundException();
        }

        $targetSiteId = Craftliltplugin::getInstance()
            ->languageMapper
            ->getSiteIdByLanguage($translationTargetLanguage);
        $parentElementId = $element->getCanonicalId() ?? $elementId;

        if (!isset($unprocessedTranslations[$parentElementId][$targetSiteId])) {
            //TODO: handle when element not found?
            throw new ElementNotFoundException();
        }

        $translationRecord = $unprocessedTranslations[$parentElementId][$targetSiteId];

        if (empty($translationRecord->translatedDraftId)) {
            $translationRecord->translatedDraftId = $element->getId();
        }

        return $translationRecord;
    }
}
