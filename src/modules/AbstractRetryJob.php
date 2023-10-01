<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\queue\BaseJob;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;

abstract class AbstractRetryJob extends BaseJob
{
    /**
     * Lilt plugin internal job id
     *
     * @var int
     */
    public $jobId;

    /**
     * @var int
     */
    public $attempt = 0;

    /**
     *
     * Is current job is eligible for retry
     *
     * @return bool
     */
    abstract public function canRetry(): bool;

    abstract public function getRetryJob(): BaseJob;

    protected function getCommand(): ?Command
    {
        if (!Craft::$app->getMutex()->acquire($this->getMutexKey())) {
            Craft::error(sprintf('Job %s is already processing job %d', __CLASS__, $this->jobId));

            return null;
        }

        $jobId = $this->jobId;
        $job = Job::findOne(['id' => $jobId]);

        if (!$job) {
            return null;
        }

        $jobRecord = JobRecord::findOne(['id' => $jobId]);

        if (!$jobRecord) {
            Craft::error(sprintf("Can't find JobRecord for job id: %d", $jobId));

            return null;
        }

        return new Command($job, $jobRecord);
    }

    protected function release(): void
    {
        Craft::$app->getMutex()->release($this->getMutexKey());
    }

    protected function getMutexKey(): string
    {
        return __CLASS__ . '_' . __FUNCTION__ . '_' . $this->jobId;
    }
}
