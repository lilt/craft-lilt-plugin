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
}
