<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\helpers\Queue as CraftHelpersQueue;
use craft\queue\BaseJob;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\LiltLogger;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\services\repositories\SettingsRepository;

class QueueManager extends BaseJob
{
    public const PRIORITY = 512;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $queueDisableAutomaticSync = (bool) Craftliltplugin::getInstance()->settingsRepository->get(
            SettingsRepository::QUEUE_DISABLE_AUTOMATIC_SYNC
        );

        if ($queueDisableAutomaticSync) {
            // skip because automatic sync is disabled
            return;
        }

        $mutex = Craft::$app->getMutex();
        $mutexKey = self::getMutexKey();
        if (!$mutex->acquire($mutexKey)) {
            LiltLogger::warning('Lilt queue manager is already running');

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
            LiltLogger::info([
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

        LiltLogger::info([
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

    public static function getMutexKey(): string
    {
        return __CLASS__ . '_' . __FUNCTION__;
    }
}
