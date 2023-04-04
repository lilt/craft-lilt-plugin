<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\translation;

use Craft;
use craft\base\ElementInterface;
use lilthq\craftliltplugin\controllers\job\AbstractJobController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Translation;
use lilthq\craftliltplugin\records\TranslationRecord;
use Throwable;
use yii\web\Response;

class PostTranslationPublishController extends AbstractJobController
{
    protected $allowAnonymous = false;

    /**
     * @throws Throwable
     */
    public function actionInvoke(): Response
    {
        $request = Craft::$app->getRequest();
        $translationIds = $request->getBodyParam('translationIds');

        if (empty($translationIds)) {
            return (new Response())->setStatusCode(400);
        }

        $translations = TranslationRecord::findAll(
            ['id' => $translationIds]
        );

        if (empty($translations)) {
            return (new Response())->setStatusCode(404);
        }

        foreach ($translations as $translation) {
            Craftliltplugin::getInstance()->publishDraftsHandler->__invoke(
                $translation->translatedDraftId,
                $translation->targetSiteId
            );

            $this->mergeCanonicalForAllDrafts($translation);
        }

        $updated = TranslationRecord::updateAll(
            ['status' => TranslationRecord::STATUS_PUBLISHED],
            ['id' => $translationIds]
        );

        if ($updated) {
            foreach ($translations as $translation) {
                Craftliltplugin::getInstance()->jobLogsRepository->create(
                    $translation->jobId,
                    Craft::$app->getUser()->getId(),
                    sprintf('Translation (id: %d) published', $translation->id)
                );
            }

            Craftliltplugin::getInstance()->refreshJobStatusHandler->__invoke(
                $translations[0]->jobId
            );
        }

        Craft::$app->elements->invalidateCachesForElementType(Translation::class);

        return $this->asJson([
            'success' => $updated === 1
        ]);
    }

    /**
     * @param $translations
     * @param TranslationRecord $translation
     * @return ElementInterface|null
     */
    private function mergeCanonicalForAllDrafts(
        TranslationRecord $translation
    ): void {
        $translationsToUpdate = TranslationRecord::findAll(
            [
                'jobId' => $translation->jobId,
                'status' => [TranslationRecord::STATUS_READY_FOR_REVIEW, TranslationRecord::STATUS_READY_TO_PUBLISH]
            ]
        );

        foreach ($translationsToUpdate as $translationToUpdate) {
            if ((int) $translation->id === (int) $translationToUpdate->id) {
                //we don't need to update current translation
                continue;
            }

            Craft::info(
                sprintf(
                    'Merge canonical changes for %d site %s',
                    $translationToUpdate->translatedDraftId,
                    Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId(
                        $translationToUpdate->targetSiteId
                    )
                )
            );

            $draftElement = Craft::$app->elements->getElementById(
                $translationToUpdate->translatedDraftId,
                null,
                $translation->targetSiteId
            );

            if (!$draftElement) {
                throw new \RuntimeException('Draft not found');
            }

            Craft::$app->getElements()->mergeCanonicalChanges($draftElement);

            Craft::$app->getElements()->saveElement($draftElement, true, false);

            Craft::$app->getElements()->invalidateCachesForElement($draftElement);
            Craft::$app->getElements()->invalidateCachesForElement($draftElement->getCanonical());
        }
    }
}
