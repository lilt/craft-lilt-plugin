<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\helpers\Queue;
use craft\web\Controller;
use LiltConnectorSDK\ApiException;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\SendJobToConnector;
use lilthq\craftliltplugin\records\JobRecord;
use Throwable;
use yii\base\Exception;
use yii\db\StaleObjectException;
use yii\web\Response;

class GetSendToLiltController extends Controller
{
    protected $allowAnonymous = false;

    /**
     * @throws ElementNotFoundException
     * @throws Throwable
     * @throws MissingComponentException
     * @throws ApiException
     * @throws StaleObjectException
     * @throws Exception
     */
    public function actionInvoke(): Response
    {
        $request = Craft::$app->getRequest();

        if (!$request->getIsGet()) {
            return (new Response())->setStatusCode(405);
        }

        $jobId = (int)$request->getSegment(4);

        $job = Job::findOne(['id' => $jobId]);
        $jobRecord = JobRecord::findOne(['id' => $jobId]);

        if (!$job || !$jobRecord) {
            return (new Response())->setStatusCode(404);
        }

        $mutex = Craft::$app->getMutex();
        $mutexKey = __CLASS__ . '_' . __FUNCTION__ . '_' . $jobId;

        if (!$mutex->acquire($mutexKey)) {
            // Job is already in progress
            return $this->redirect($job->getCpEditUrl());
        }

        if ($job->status === Job::STATUS_IN_PROGRESS) {
            //TODO: check if job exist
            return $this->redirect($job->getCpEditUrl());
        }

        Queue::push(
            new SendJobToConnector(['jobId' => $jobId]),
            SendJobToConnector::PRIORITY,
            SendJobToConnector::DELAY_IN_SECONDS
        );

        $jobRecord->status = Job::STATUS_IN_PROGRESS;
        $jobRecord->save();

        Craft::$app->elements->invalidateCachesForElement($job);

        $mutex->release($mutexKey);

        return $this->redirect($job->getCpEditUrl());
    }
}
