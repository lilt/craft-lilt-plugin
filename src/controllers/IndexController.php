<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers;

use craft\web\Controller;
use yii\web\Response;

class IndexController extends Controller
{
    public function actionIndex(): Response
    {
        return $this->renderTemplate(
            'craft-lilt-plugin/jobs.twig'
        );
    }
}
