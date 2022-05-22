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
use lilthq\craftliltplugin\services\job\CreateJobCommand;
use yii\base\InvalidConfigException;
use yii\web\Response;

class PostCreateJobController extends AbstractJobController
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

        if ($job->hasErrors()) {
            Craft::$app->getSession()->setFlash(
                'cp-error',
                'Couldn’t create job.'
            );

            return $this->renderJobForm($job);
        }

        if($job->versions === '[]') {
            //TODO: fix FE part
            $job->versions = [];
        }

        $command = new CreateJobCommand(
            $job->title,
            $job->elementIds,
            $job->targetSiteIds,
            $job->sourceSiteId,
            $job->translationWorkflow,
            $job->versions
        );

        $asDraft = $request->getBodyParam('actionButton') === 'create-job-draft';

        $job = Craftliltplugin::getInstance()->createJobHandler->__invoke(
            $command, $asDraft
        );

        Craft::$app->getCache()->flush();

        Craft::$app->getSession()->setFlash(
            'cp-notice',
            'Translate job created successfully.'
        );

        return $this->redirectToPostedUrl(); //$this->redirect($job->getCpEditUrl());
    }
}
