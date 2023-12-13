<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;

class Command
{
    /** @var Job */
    private $job;

    /** @var JobRecord */
    private $jobRecord;

    /**
     * @param Job $job
     * @param JobRecord $jobRecord
     */
    public function __construct(Job $job, JobRecord $jobRecord)
    {
        $this->job = $job;
        $this->jobRecord = $jobRecord;
    }

    /**
     * @return Job
     */
    public function getJob(): Job
    {
        return $this->job;
    }

    /**
     * @return JobRecord
     */
    public function getJobRecord(): JobRecord
    {
        return $this->jobRecord;
    }
}
