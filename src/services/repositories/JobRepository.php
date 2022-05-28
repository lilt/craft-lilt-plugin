<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories;

use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;

class JobRepository
{
    public function findOneById(int $id): ?Job
    {
        return Job::findOne(['id' => $id]);
    }

    /**
     * @return Job[]
     */
    public function findByIds(array $ids): array
    {
        return Job::findAll(['id' => $ids]);
    }

    public function saveJob(Job $job): bool
    {
        $jobRecord = new JobRecord();
        $jobRecord->setAttributes($job->getAttributes(), false);
        $jobRecord->save();

        return true;
    }
}
