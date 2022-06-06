<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use craft\web\Controller;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use yii\web\Response;

class GetSyncFromLiltController extends Controller
{
    protected $allowAnonymous = false;

    public function actionInvoke(): Response
    {
        $request = Craft::$app->getRequest();

        if (!$request->getIsGet()) {
            return (new Response())->setStatusCode(405);
        }

        $jobId = (int) $request->getSegment(4);
        $job = Job::findOne(['id' => $jobId]);

        if (!$job) {
            return (new Response())->setStatusCode(404);
        }

        Craftliltplugin::getInstance()->syncJobFromLiltConnectorHandler->__invoke($job);

        Craft::$app->getSession()->setFlash(
            'cp-notice',
            'Job uploaded to Lilt platform successfully'
        );

        return $this->redirect($job->getCpEditUrl());
    }
}
