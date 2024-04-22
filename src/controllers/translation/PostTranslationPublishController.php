<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\translation;

use Craft;
use lilthq\craftliltplugin\controllers\job\AbstractJobController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\handlers\commands\PublishDraftCommand;
use lilthq\craftliltplugin\services\handlers\PublishDraftAsyncHandler;
use lilthq\craftliltplugin\services\handlers\PublishDraftHandler;
use lilthq\craftliltplugin\services\repositories\SettingsRepository;
use Throwable;
use yii\web\Response;

class PostTranslationPublishController extends AbstractJobController
{
    protected array|int|bool $allowAnonymous = false;

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

        $publishHandler = $this->getPublishHandler();

        foreach ($translations as $translation) {
            $publishHandler->__invoke(
                new PublishDraftCommand(
                    $translation->translatedDraftId,
                    $translation->targetSiteId,
                    $translation->jobId,
                    $translation->id
                )
            );
        }

        Craftliltplugin::getInstance()->refreshJobStatusHandler->__invoke(
            $translations[0]->jobId
        );

        return $this->asJson([
            'success' => true
        ]);
    }

    /**
     * @return PublishDraftAsyncHandler|PublishDraftHandler
     */
    private function getPublishHandler()
    {
        if (
            Craftliltplugin::getInstance()
                ->settingsRepository
                ->getBool(SettingsRepository::PUBLISH_TRANSLATIONS_ASYNC)
        ) {
            return Craftliltplugin::getInstance()->publishDraftsHandlerAsync;
        }

        return Craftliltplugin::getInstance()->publishDraftsHandler;
    }
}
