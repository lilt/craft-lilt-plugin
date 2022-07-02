<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use lilthq\craftliltplugin\Craftliltplugin;
use Throwable;
use yii\web\Response;

class GetTranslationReviewController extends AbstractJobController
{
    protected array|int|bool $allowAnonymous = false;

    /**
     * @throws Throwable
     */
    public function actionInvoke(): Response
    {
        $request = Craft::$app->getRequest();
        $translationId = $request->getParam('translationId');

        if (empty($translationId)) {
            return $this->response->setStatusCode(400);
        }

        $translation = Craftliltplugin::getInstance()->translationRepository->findOneById(
            (int) $translationId
        );

        if ($translation === null) {
            return $this->response->setStatusCode(404);
        }

        $render = $this->renderTemplate(
            'craft-lilt-plugin/_components/translation/_overview.twig',
            [
                'previewUrl' => $translation->getPreviewUrl(),
                'originalUrl' => $translation->getElementUrl(),
                'translation' => $translation,
            ]
        );

        return $this->asJson([
            'html' => $render->data
        ]);
    }
}
