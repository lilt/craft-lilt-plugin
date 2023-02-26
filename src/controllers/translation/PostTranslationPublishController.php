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
use lilthq\craftliltplugin\records\SettingRecord;
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

            $this->mergeCanonicalForAllDrafts($translation->jobId, $translation);
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
        int $jobId,
        TranslationRecord $translation
    ): void {
        $translationsToUpdate = TranslationRecord::findAll(
            [
                'jobId' => $jobId,
                'status' => [TranslationRecord::STATUS_READY_FOR_REVIEW, TranslationRecord::STATUS_READY_TO_PUBLISH]
            ]
        );

        foreach ($translationsToUpdate as $translationToUpdate) {
            $draftElement = Craft::$app->elements->getElementById(
                $translationToUpdate->translatedDraftId,
                null,
                $translation->targetSiteId
            );

            if (!$draftElement) {
                throw new \RuntimeException('Draft not found');
            }

            Craftliltplugin::getInstance()->createDraftHandler->markFieldsAsChanged(
                $draftElement->getCanonical()
            );
            $attributes = ['title'];

            $copyEntriesSlugFromSourceToTarget = SettingRecord::findOne(
                ['name' => 'copy_entries_slug_from_source_to_target']
            );
            $isCopySlugEnabled = (bool) ($copyEntriesSlugFromSourceToTarget->value ?? false);

            if ($isCopySlugEnabled) {
                $attributes[] = 'slug';
            }
            Craftliltplugin::getInstance()->createDraftHandler->upsertChangedAttributes($draftElement->getCanonical(false), $attributes);


            Craftliltplugin::getInstance()->publishDraftsHandler->mergeCanonicalChanges(
                $draftElement
            );

            Craft::$app->elements->invalidateCachesForElement($draftElement->getCanonical());
            Craft::$app->elements->invalidateCachesForElement($draftElement);
        }
    }
}