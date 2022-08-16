<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\errors\ElementNotFoundException;
use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\datetime\DateTime;
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
    ): ?TranslationRecord {
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
            Craft::error([
                'message' => "Can't find element!",
                'target_language' => $translationTargetLanguage,
                'element_id' => $elementId,
                'source_site_id' => $job->sourceSiteId,
                'unprocessed_translations' => $unprocessedTranslations,
                'translation_response' => $translationResponse->jsonSerialize(),
            ]);

            return null;
        }

        $targetSiteId = Craftliltplugin::getInstance()
            ->languageMapper
            ->getSiteIdByLanguage($translationTargetLanguage);
        $parentElementId = $element->getCanonicalId() ?? $elementId;

        if (!isset($unprocessedTranslations[$parentElementId][$targetSiteId])) {
            Craft::error([
                'message' => "Can't find translation!",
                'target_language' => $translationTargetLanguage,
                'parent_elementId' => $parentElementId,
                'target_site_id' => $targetSiteId,
                'unprocessed_translations' => $unprocessedTranslations,
                'translation_response' => $translationResponse->jsonSerialize(),
            ]);

            return null;
        }

        //TODO: get rid of it, we can use repository here
        $translationRecord = $unprocessedTranslations[$parentElementId][$targetSiteId];

        if (empty($translationRecord->connectorTranslationId)) {
            $translationRecord->connectorTranslationId = $translationResponse->getId();
        }

        $translationRecord->status = TranslationRecord::STATUS_FAILED;
        $translationRecord->lastDelivery = new DateTime();
        $translationRecord->save();

        return $translationRecord;
    }
}
