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
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\services\job\EditJobCommand;
use RuntimeException;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

class PostPublishDraftJobController extends AbstractPostJobController
{
    protected $allowAnonymous = false;

    /**
     * @throws InvalidConfigException
     * @throws MissingComponentException
     * @throws MethodNotAllowedHttpException
     * @throws BadRequestHttpException
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
            $job->versions,
            Job::STATUS_NEW
        );

        Craftliltplugin::getInstance()->editJobHandler->__invoke(
            $command
        );

        Craft::$app->getCache()->flush();

        Craft::$app->getSession()->setFlash(
            'cp-notice',
            'Translate job draft published.'
        );


        $redirectUrl = $this->request->getValidatedBodyParam('redirect');
        if ($redirectUrl === null || $redirectUrl === '{cpEditUrl}') {
            return $this->redirect($job->getCpEditUrl());
        }

        return $this->redirectToPostedUrl();
    }
}
