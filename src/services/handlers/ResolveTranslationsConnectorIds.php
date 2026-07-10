<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\exceptions\WrongTranslationFilenameException;
use lilthq\craftliltplugin\LiltLogger;
use lilthq\craftliltplugin\records\TranslationRecord;
use RuntimeException;

class ResolveTranslationsConnectorIds
{
    /**
     * @throws ApiException
     */
    public function update(Job $job): array
    {
        $connectorTranslations = Craftliltplugin::getInstance()
            ->connectorTranslationRepository
            ->findByJobId($job->liltJobId);

        $connectorTranslationsMapped = $this->mapConnectorTranslations($connectorTranslations->getResults());

        $translationResponses = $this->filterTranslationResponses($connectorTranslationsMapped);

        return $this->updateTranslationRecords($translationResponses, $job);
    }

    private function mapConnectorTranslations(array $connectorTranslations): array
    {
        $connectorTranslationsMapped = [];
        foreach ($connectorTranslations as $translationResponse) {
            try {
                $elementId = Craftliltplugin::getInstance()
                    ->connectorTranslationRepository
                    ->getElementIdFromTranslationResponse($translationResponse);
            } catch (WrongTranslationFilenameException $ex) {
                LiltLogger::error(sprintf("Can't get element id from file: %s", $translationResponse->getName()));
                continue;
            }

            $targetLanguage = $this->getTranslationTargetLanguage($translationResponse);
            $connectorTranslationsMapped[$elementId][$targetLanguage][] = $translationResponse;
        }

        return $connectorTranslationsMapped;
    }

    private function filterTranslationResponses(array $connectorTranslationsMapped): array
    {
        $translationResponses = [];
        foreach ($connectorTranslationsMapped as $elementId => $targetLanguages) {
            foreach ($targetLanguages as $targetLanguage => $translations) {
                if (count($translations) === 1) {
                    $translationResponses[] = $translations[0];
                    continue;
                }

                $translationResponses[] = $this->findCompleteTranslationResponses($translations);
            }
        }

        return $translationResponses;
    }

    /**
     * @param TranslationResponse[] $translations
     * @return TranslationResponse
     */
    private function findCompleteTranslationResponses(array $translations): TranslationResponse
    {
        $result = null;
        foreach ($translations as $translation) {
            // Search for complete translation
            if (
                $translation->getStatus() == TranslationResponse::STATUS_EXPORT_COMPLETE
                || $translation->getStatus() == TranslationResponse::STATUS_MT_COMPLETE
            ) {
                $result = $translation;
                break;
            }
        }

        // Fallback to first translation
        if ($result === null) {
            $result = $translations[0];
        }

        return $result;
    }

    private function updateTranslationRecords(array $translationResponses, Job $job): array
    {
        $result = [];
        foreach ($translationResponses as $translationResponse) {
            try {
                $elementId = Craftliltplugin::getInstance()
                    ->connectorTranslationRepository
                    ->getElementIdFromTranslationResponse($translationResponse);
            } catch (WrongTranslationFilenameException $ex) {
                LiltLogger::error(sprintf("Can't get element id from file: %s", $translationResponse->getName()));
                continue;
            }

            $targetLanguage = $this->getTranslationTargetLanguage($translationResponse);
            $siteId = Craftliltplugin::getInstance()
                ->languageMapper
                ->getSiteIdByLanguage(trim($targetLanguage, '-'));
            $versionId = $job->getElementVersionId($elementId);

            $translationRecord = TranslationRecord::findOne([
                'targetSiteId' => $siteId,
                'versionId' => $versionId,
                'jobId' => $job->id
            ]);

            if ($translationRecord === null) {
                throw new RuntimeException(sprintf(
                    "Can't find translation for target %s, jobId %d, versionId %d",
                    trim($targetLanguage, '-'),
                    $job->id,
                    $versionId
                ));
            }

            $translationRecord->connectorTranslationId = $translationResponse->getId();
            $translationRecord->save();

            $result[$translationResponse->getId()] = $translationResponse;
        }

        return array_values($result);
    }

    private function getTranslationTargetLanguage(TranslationResponse $translationResponse): string
    {
        if (empty($translationResponse->getTrgLocale())) {
            return $translationResponse->getTrgLang();
        }

        return sprintf('%s-%s', $translationResponse->getTrgLang(), $translationResponse->getTrgLocale());
    }
}
