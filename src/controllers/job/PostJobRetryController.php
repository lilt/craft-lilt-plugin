<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use craft\web\Controller;
use lilthq\craftliltplugin\Craftliltplugin;
use yii\web\Response;

class PostJobRetryController extends Controller
{
    public function actionInvoke(): Response
    {
        $request = Craft::$app->getRequest();

        $jobIds = $request->getParam('jobIds', []);

        if (count($jobIds) === 0) {
            return $this->response->setStatusCode(400, json_encode(['msg' => 'Empty jobs']));
        }

        $jobs = Craftliltplugin::getInstance()->jobRepository->findByIds(
            $jobIds
        );

        $userId = Craft::$app->getUser()->getId();

        foreach ($jobs as $job) {
            Craftliltplugin::getInstance()->jobLogsRepository->create(
                $job->id,
                $userId,
                sprintf('Job retried (previous Lilt Job ID: %d)', $job->liltJobId)
            );

            Craftliltplugin::getInstance()->sendJobToLiltConnectorHandler->__invoke($job);
        }


        if (count($jobs) === 0) {
            return $this->response->setStatusCode(404, json_encode(['msg' => 'No jobs found']));
        }

        return $this->response->setStatusCode(200);
    }
}
