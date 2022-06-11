<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use craft\web\Controller;
use lilthq\craftliltplugin\Craftliltplugin;
use yii\web\Response;

class PostSyncController extends Controller
{
    public function actionInvoke(): Response
    {
        $request = Craft::$app->getRequest();

        $jobIds = $request->getBodyParam('jobIds', []);

        if (count($jobIds) === 0) {
            return $this->response->setStatusCode(400, json_encode(['msg' => 'Empty jobs']));
        }

        $jobs = Craftliltplugin::getInstance()->jobRepository->findByIds(
            $jobIds
        );

        foreach ($jobs as $job) {
            Craftliltplugin::getInstance()->syncJobFromLiltConnectorHandler->__invoke($job);
        }

        if (count($jobs) === 0) {
            return $this->response->setStatusCode(400, json_encode(['msg' => 'Job not found']));
        }

        return $this->response->setStatusCode(200);
    }
}
