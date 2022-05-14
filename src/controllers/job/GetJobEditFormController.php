<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use craft\errors\ElementNotFoundException;
use LiltConnectorSDK\Model\JobResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\Response;

class GetJobEditFormController extends AbstractJobController
{
    protected $allowAnonymous = false;

    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function actionInvoke(): Response
    {
        //TODO: check why get param is not working
        //$request->getParam('jobId')
        $request = Craft::$app->getRequest();
        $jobId = (int)$request->getSegment(4);

        if (empty($jobId) || !$request->getIsGet()) {
            return (new Response())->setStatusCode(405);
        }

        $job = Job::findOne(['id' => $jobId]);

        //TODO: move to separate class, maybe post request from FE
        if ($job->liltJobId !== null) {
            $liltJob = Craftliltplugin::getInstance()->connectorJobRepository->findOneById(
                (int)$job->liltJobId
            );
            $jobRecord = JobRecord::findOne(['id' => $job->getId()]);

            if ($jobRecord === null) {
                return (new Response())->setStatusCode(404);
            }

            if ($liltJob->getStatus() === JobResponse::STATUS_FAILED) {
                $job->status = Job::STATUS_FAILED;
            }

            if ($liltJob->getStatus() === JobResponse::STATUS_COMPLETE) {
                $job->status = Job::STATUS_READY_FOR_REVIEW;
            }

            $jobRecord->setAttributes($job->getAttributes(), false);

            Craft::$app->getElements()->saveElement(
                $job,
                true,
                true,
                true
            );

            $jobRecord->save();
        }

        if (!$job) {
            return (new Response())->setStatusCode(404);
        }

        return $this->renderJobForm(
            $job,
            [
                'showLiltTranslateButton' => $job->getStatus() === Job::STATUS_NEW,
                'showLiltSyncButton' =>  $job->getStatus() === Job::STATUS_READY_FOR_REVIEW,
                'isUnpublishedDraft' => false,
                'sendToLiltActionLink' => 'craft-lilt-plugin/job/send-to-lilt/' . $jobId,
                'syncFromLiltActionLink' => 'craft-lilt-plugin/job/sync-from-lilt/' . $jobId,
            ],
            'craft-lilt-plugin/job/edit.twig'
        );
    }
}
