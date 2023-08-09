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
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\services\handlers\commands\EditJobCommand;
use yii\base\InvalidConfigException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

class PostEditJobController extends AbstractPostJobController
{
    protected array|int|bool $allowAnonymous = false;

    /**
     * @throws InvalidConfigException
     * @throws MissingComponentException
     * @throws MethodNotAllowedHttpException
     */
    public function actionInvoke(): Response
    {
        $job = $this->getJob();

        if (!$job->id) {
            throw new \RuntimeException('Job id cant be empty');
        }

        if ($job->hasErrors()) {
            $jobRecord = JobRecord::findOne(['id' => $job->id]);
            $job->status = $jobRecord->status ?? Job::STATUS_DRAFT;
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
            $job->getVersions()
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
