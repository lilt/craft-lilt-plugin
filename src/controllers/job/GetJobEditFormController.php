<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use craft\errors\ElementNotFoundException;
use craft\helpers\UrlHelper;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
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
    public function actionInvoke(string $jobId): Response
    {
        if (empty($jobId)) {
            return (new Response())->setStatusCode(400);
        }

        $job = Job::findOne(['id' => (int) $jobId]);

        if (!$job) {
            return (new Response())->setStatusCode(404);
        }

        return $this->renderJobForm(
            $job,
            [
                'jobLogs' => Craftliltplugin::getInstance()->jobLogsRepository->findByJobId(
                    $job->getId()
                ),
                'showLiltTranslateButton' => $job->getStatus() === Job::STATUS_NEW,
                'showLiltSyncButton' =>  $job->getStatus() === Job::STATUS_READY_FOR_REVIEW,
                'isUnpublishedDraft' => false,
                'sendToLiltActionLink' => 'craft-lilt-plugin/job/send-to-lilt/' . $jobId,
                'syncFromLiltActionLink' => 'craft-lilt-plugin/job/sync-from-lilt/' . $jobId,
                'crumbs' => [
                    [
                        'label' => 'Lilt Plugin',
                        'url' => UrlHelper::cpUrl('admin/craft-lilt-plugin')
                    ],
                    [
                        'label' => 'Jobs',
                        'url' => UrlHelper::cpUrl('admin/craft-lilt-plugin/jobs')
                    ],
                ]
            ],
            'craft-lilt-plugin/job/edit.twig'
        );
    }
}
