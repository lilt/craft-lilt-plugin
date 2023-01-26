<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\errors\InvalidFieldException;
use craft\queue\BaseJob;
use LiltConnectorSDK\ApiException;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use Throwable;
use yii\queue\RetryableJobInterface;

class SendJobToConnector extends BaseJob implements RetryableJobInterface
{
    public const DELAY_IN_SECONDS = 10;
    public const PRIORITY = 256;
    public const TTR = 60 * 30;

    private const RETRY_COUNT = 3;

    /**
     * @var int $jobId
     */
    public $jobId;

    /**
     * @inheritdoc
     *
     * @throws ApiException
     * @throws Throwable
     * @throws InvalidFieldException
     */
    public function execute($queue): void
    {
        //TODO: seems to be same for all jobs, let's move it to abstract class
        $jobId = $this->jobId;
        $job = Job::findOne(['id' => $jobId]);

        if (!$job) {
            //TODO: how it is possible?
            return;
        }

        $jobRecord = JobRecord::findOne(['id' => $jobId]);

        if (!$jobRecord) {
            Craft::error(sprintf("Can't find JobRecord for job id: %d", $jobId));

            return;
        }

        $mutex = Craft::$app->getMutex();
        $mutexKey = __CLASS__ . '_' . __FUNCTION__ . '_' . $this->jobId;

        if (!$mutex->acquire($mutexKey)) {
            Craft::error(sprintf('Job %s is already processing job %d', __CLASS__, $this->jobId));

            return;
        }

        if ($job->isVerifiedFlow() || $job->isInstantFlow()) {
            Craftliltplugin::getInstance()->sendJobToLiltConnectorHandler->__invoke($job);
        }

        if ($job->isCopySourceTextFlow()) {
            Craftliltplugin::getInstance()->copySourceTextHandler->__invoke($job);
        }

        $this->markAsDone($queue);
        $mutex->release($mutexKey);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Craft::t('app', 'Sending jobs to lilt');
    }

    /**
     * @param $queue
     * @return void
     */
    private function markAsDone($queue): void
    {
        $this->setProgress(
            $queue,
            1,
            Craft::t(
                'app',
                'Sending translations for jobId: {jobId} to lilt platform done',
                [
                    'jobId' => $this->jobId,
                ]
            )
        );
    }

    public function getTtr(): int
    {
        return self::TTR;
    }

    public function canRetry($attempt, $error): bool
    {
        return $attempt < self::RETRY_COUNT;
    }
}
