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
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use Throwable;
use yii\web\Response;

class PostTranslationReviewController extends AbstractJobController
{
    protected $allowAnonymous = false;

    /**
     * @throws Throwable
     */
    public function actionInvoke(): Response
    {
        $request = Craft::$app->getRequest();
        $translationId = $request->getBodyParam('translationId');

        if (empty($translationId)) {
            return (new Response())->setStatusCode(400);
        }

        $translation = Craftliltplugin::getInstance()->translationRepository->findOneById((int) $translationId);

        if ($translation === null) {
            return (new Response())->setStatusCode(404);
        }

        $updated = TranslationRecord::updateAll(
            ['status' => TranslationRecord::STATUS_READY_TO_PUBLISH],
            ['id' => $translation->id]
        );

        if ($updated) {
            Craftliltplugin::getInstance()->jobLogsRepository->create(
                $translation->jobId,
                Craft::$app->getUser()->getId(),
                sprintf('Translation (id: %d) reviewed', $translation->id)
            );

            Craftliltplugin::getInstance()->refreshJobStatusHandler->__invoke(
                $translation->jobId
            );
        }

        if ($updated !== 1) {
            //TODO: handle when we update more then one row
        }

        return $this->asJson([
            'success' => $updated === 1
        ]);
    }
}
