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
use lilthq\craftliltplugin\services\handlers\commands\CreateJobCommand;
use yii\base\InvalidConfigException;
use yii\web\Response;

class PostCreateJobController extends AbstractPostJobController
{
    protected array|int|bool $allowAnonymous = false;

    /**
     * @throws InvalidConfigException
     * @throws MissingComponentException
     */
    public function actionInvoke(): Response
    {
        $job = $this->getJob();

        if ($job->hasErrors()) {
            $job->status = Job::STATUS_DRAFT;
            Craft::$app->getSession()->setFlash(
                'cp-error',
                'Couldn’t create job.'
            );

            return $this->renderJobForm($job);
        }

        $command = new CreateJobCommand(
            $job->title,
            $job->elementIds,
            $job->targetSiteIds,
            $job->sourceSiteId,
            $job->translationWorkflow,
            $job->versions,
            $job->authorId
        );

        $saveDraft = (int)Craft::$app->getRequest()->getBodyParam('saveDraft');
        $asDraft = ($saveDraft === 1);

        $job = Craftliltplugin::getInstance()->createJobHandler->__invoke(
            $command,
            $asDraft
        );

        Craft::$app->getCache()->flush();

        Craft::$app->getSession()->setFlash(
            'cp-notice',
            'Translate job created successfully.'
        );

        $redirectUrl = $this->request->getValidatedBodyParam('redirect');
        if ($redirectUrl === null || $redirectUrl === '{cpEditUrl}') {
            return $this->redirect($job->getCpEditUrl());
        }

        return $this->redirectToPostedUrl();
    }
}
