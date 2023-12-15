<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\queue\BaseJob;
use craft\helpers\Queue as CraftHelpersQueue;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\JobRecord;

class QueueManager extends BaseJob
{
    public const PRIORITY = 512;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $mutex = Craft::$app->getMutex();
        $mutexKey = __CLASS__ . '_' . __FUNCTION__;
        if (!$mutex->acquire($mutexKey)) {
            Craft::warning('Lilt queue manager is already running');

            $this->setProgress(
                $queue,
                1,
                Craft::t(
                    'app',
                    'Finished lilt queue manager'
                )
            );

            return;
        }

        $jobRecords = JobRecord::findAll([
            'status' => Job::STATUS_IN_PROGRESS,
            'translationWorkflow' => [
                CraftliltpluginParameters::TRANSLATION_WORKFLOW_VERIFIED,
                CraftliltpluginParameters::TRANSLATION_WORKFLOW_INSTANT
            ],
        ]);

        if (count($jobRecords) === 0) {
            Craft::info([
                'message' => 'No jobs found in progress ',
                'queue' => __FILE__,
            ]);

            $this->setProgress(
                $queue,
                1,
                Craft::t(
                    'app',
                    'Finished lilt queue manager'
                )
            );

            return;
        }

        $jobIds = array_map(function (JobRecord $jobRecord) {
            return $jobRecord->id;
        }, $jobRecords);


        CraftHelpersQueue::push(
            new ManualJobSync(['jobIds' => $jobIds]),
            SendJobToConnector::PRIORITY,
            0
        );

        Craft::info([
            'message' => 'Push jobs in progress for manual sync',
            'jobIds' => $jobIds,
            'queue' => __FILE__,
        ]);

        $this->setProgress(
            $queue,
            1,
            Craft::t(
                'app',
                'Finished lilt queue manager'
            )
        );

        $mutex->release($mutexKey);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Craft::t('app', 'Lilt queue manager');
    }
}
