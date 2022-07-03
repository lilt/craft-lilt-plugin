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

        $elementIds = $this->request->getQueryParam('elementIds', []);
        $statuses = $this->request->getQueryParam('statuses', []);

        $criteria = [];

        if (!empty($elementIds)) {
            $criteria['where']['elements.id'] = $elementIds;
        }

        if (!empty($statuses)) {
            $criteria['where']['lilt_jobs.status'] = $statuses;
        }

        return $this->renderTemplate(
            'craft-lilt-plugin/jobs.twig',
            [
                'criteria' => $criteria,
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
