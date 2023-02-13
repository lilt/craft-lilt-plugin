<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\exceptions\WrongTranslationFilenameException;
use lilthq\craftliltplugin\records\TranslationRecord;
use RuntimeException;

class UpdateTranslationsConnectorIds
{
    public function update(Job $job): void
    {
        $connectorTranslations = Craftliltplugin::getInstance()->connectorTranslationRepository->findByJobId(
            $job->liltJobId
        );

        foreach ($connectorTranslations->getResults() as $translationResponse) {
            try {
                $elementId = Craftliltplugin::getInstance(
                )->connectorTranslationRepository->getElementIdFromTranslationResponse($translationResponse);
            } catch (WrongTranslationFilenameException $ex) {
                continue;
            }

            if (empty($translationResponse->getTrgLocale())) {
                $targetLanguage = $translationResponse->getTrgLang();
            } else {
                $targetLanguage = sprintf(
                    '%s-%s',
                    $translationResponse->getTrgLang(),
                    $translationResponse->getTrgLocale()
                );
            }

            $translationRecord = TranslationRecord::findOne([
                'targetSiteId' => Craftliltplugin::getInstance()
                    ->languageMapper
                    ->getSiteIdByLanguage(
                        trim($targetLanguage, '-')
                    ),
                'versionId' => $job->getElementVersionId($elementId),
                'jobId' => $job->id
            ]);

            if ($translationRecord === null) {
                throw new RuntimeException(
                    sprintf(
                        "Can't find translation for target %s, jobId %d, versionId %d",
                        trim($targetLanguage, '-'),
                        $job->id,
                        $job->getElementVersionId($elementId)
                    )
                );
            }

            $translationRecord->connectorTranslationId = $translationResponse->getId();
            $translationRecord->save();
        }
    }
}
