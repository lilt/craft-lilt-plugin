<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use craft\helpers\Queue;
use craft\web\Controller;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\elements\Translation;
use lilthq\craftliltplugin\modules\ManualJobSync;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationNotificationsRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
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

        if (count($jobs) === 0) {
            return $this->response->setStatusCode(404, json_encode(['msg' => 'Job(s) not found']));
        }

        $selectedJobIds = [];
        foreach ($jobs as $job) {
            if ($job->isCopySourceTextFlow()) {
                continue;
            }

            if (
                $job->status === Job::STATUS_NEW
                || $job->status === Job::STATUS_DRAFT
                || $job->status === Job::STATUS_COMPLETE
            ) {
                continue;
            }

            $selectedJobIds[] = (int)$job->id;
        }

        sort($selectedJobIds);

        Queue::push(
            (new ManualJobSync(
                [
                    'jobIds' => $selectedJobIds,
                ]
            )),
            ManualJobSync::PRIORITY,
            ManualJobSync::DELAY_IN_SECONDS
        );

        TranslationNotificationsRecord::deleteAll(['jobId' => $selectedJobIds]);

        TranslationRecord::updateAll(
            [
                'status' => TranslationRecord::STATUS_IN_PROGRESS
            ],
            ['jobId' => $selectedJobIds]
        );

        JobRecord::updateAll(
            [
                'status' => Job::STATUS_IN_PROGRESS
            ],
            ['id' => $selectedJobIds]
        );

        Craft::$app->elements->invalidateCachesForElementType(Translation::class);
        Craft::$app->elements->invalidateCachesForElementType(Job::class);

        return $this->response->setStatusCode(200);
    }
}
