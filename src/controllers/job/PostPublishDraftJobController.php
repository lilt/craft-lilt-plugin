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
use yii\web\Response;

class PostPublishDraftJobController extends AbstractJobController
{
    protected $allowAnonymous = false;

    /**
     * @throws InvalidConfigException
     * @throws MissingComponentException
     */
    public function actionInvoke(): Response
    {
        $request = Craft::$app->getRequest();

        if (!$request->getIsPost()) {
            return (new Response())->setStatusCode(405);
        }

        $job = $this->getJobModel();
        $job->validate();

        if (!$job->id) {
            throw new RuntimeException('Job id cant be empty');
        }

        if ($job->hasErrors()) {
            return $this->renderJobForm($job);
        }

        if ($job->versions === '[]') {
            //TODO: fix FE part
            $job->versions = [];
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
