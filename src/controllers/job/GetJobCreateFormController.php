<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use lilthq\craftliltplugin\assets\JobCreateAsset;
use yii\web\Response;

class GetJobCreateFormController extends Controller
{
    protected $allowAnonymous = false;

    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \craft\errors\MissingComponentException
     */
    public function actionInvoke(): Response
    {
        $request = Craft::$app->getRequest();

        if (!$request->getIsGet()) {
            return (new Response())->setStatusCode(405);
        }

        Craft::$app->getView()->registerAssetBundle(JobCreateAsset::class);

        $availableSites = Craft::$app->getSites()->getAllSites();
        $targetLanguages = [];
        foreach ($availableSites as $availableSite) {
            $targetLanguages[$availableSite->language] = $availableSite->language;
        }

        $availableSites = [];
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $availableSites[] = [
                'value' => $site->id,
                'label' => $site->name . '(' . $site->language . ')'
            ];
        }

        return $this->renderTemplate(
            'craft-lilt-plugin/job/create.twig',
            [
                'availableSites' => $availableSites,
                'targetSites' => $targetLanguages,
                'element' => Entry::findOne(['id' => 68])
            ]
        );
    }
}