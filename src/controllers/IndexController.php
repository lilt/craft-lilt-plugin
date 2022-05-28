<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers;

use craft\helpers\UrlHelper;
use craft\web\Controller;
use lilthq\craftliltplugin\assets\JobsAsset;
use yii\web\Response;

class IndexController extends Controller
{
    public function actionIndex(): Response
    {
        $this->getView()->registerAssetBundle(JobsAsset::class);

        return $this->renderTemplate(
            'craft-lilt-plugin/jobs.twig',
            [
                'crumbs' => [
                    [
                        'label' => 'Lilt Plugin',
                        'url' => UrlHelper::cpUrl('admin/craft-lilt-plugin')
                    ],
                    [
                        'label' => 'Jobs',
                        'url' => UrlHelper::cpUrl('admin/craft-lilt-plugin/jobs')
                    ],
                ]
            ]
        );
    }
}
