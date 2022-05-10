<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers;


use craft\helpers\Queue;
use craft\web\Controller;
use LiltConnectorSDK\Api\JobsApi;
use lilthq\craftliltplugin\elements\db\JobQuery;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\modules\FetchJobStatusFromConnector;
use lilthq\craftliltplugin\records\JobRecord;
use yii\web\Response;

class IndexController extends Controller
{
    public function actionIndex(): Response
    {
        /*
        $jobsInProgress = JobRecord::findAll(['status' => Job::STATUS_IN_PROGRESS]);

        array_walk($jobsInProgress, static function (JobRecord $jobRecord) {
            Queue::push(new FetchJobStatusFromConnector([
                $jobRecord->toArray([
                    'jobId',
                    'liltJobId'
                ])
            ]));
        });
        */

        return $this->renderTemplate(
            'craft-lilt-plugin/jobs.twig'
        );
    }
}
