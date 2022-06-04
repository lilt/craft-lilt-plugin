<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use craft\errors\MissingComponentException;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\services\job\EditJobCommand;
use yii\base\InvalidConfigException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

class PostEditJobController extends AbstractPostJobController
{
    protected $allowAnonymous = false;

    /**
     * @throws InvalidConfigException
     * @throws MissingComponentException
     * @throws MethodNotAllowedHttpException
     */
    public function actionInvoke(): Response
    {
        $job = $this->getJob();

        if ($job->hasErrors()) {
            return $this->renderJobForm($job);
        }

        $command = new EditJobCommand(
            $job->id,
            $job->authorId,
            $job->title,
            $job->elementIds,
            $job->targetSiteIds,
            $job->sourceSiteId,
            $job->translationWorkflow,
            $job->versions
        );

        Craftliltplugin::getInstance()->editJobHandler->__invoke(
            $command
        );

        Craft::$app->getCache()->flush();

        Craft::$app->getSession()->setFlash(
            'cp-notice',
            'Translate job saved successfully.'
        );

        return $this->redirect($job->getCpEditUrl());
    }
}
