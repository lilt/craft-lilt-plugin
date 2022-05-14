<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use craft\errors\ElementNotFoundException;
use LiltConnectorSDK\Model\JobResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
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
        $translationId = $request->getParam('translationId');

        if (!$request->getIsPost()) {
            return (new Response())->setStatusCode(405);
        }

        if (empty($translationId)) {
            return (new Response())->setStatusCode(400);
        }

        $translation = Craftliltplugin::getInstance()->translationRepository->findOneById((int) $translationId);

        Craftliltplugin::getInstance()->publishDraftsHandler->__invoke(
            $translation->translatedDraftId, $translation->targetSiteId
        );

        $updated = TranslationRecord::updateAll(
            ['status' => TranslationRecord::STATUS_PUBLISHED], ['id' => $translation->id]
        );

        if($updated !== 1) {
            //TODO: handle when we update more then one row
        }

        return $this->asJson([
            'success' => $updated === 1
        ]);
    }
}
