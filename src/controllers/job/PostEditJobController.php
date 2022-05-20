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
use RuntimeException;
use yii\base\InvalidConfigException;
use yii\web\Response;

class PostEditJobController extends AbstractJobController
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

        if($job->versions === '[]') {
            //TODO: fix FE part
            $job->versions = [];
        }

        $command = new EditJobCommand(
            $job->id,
            $job->title,
            $job->elementIds,
            $job->targetSiteIds,
            $job->sourceSiteId,
            $job->dueDate,
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
