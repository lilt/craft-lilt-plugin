<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories;

use lilthq\craftliltplugin\records\JobLogRecord;

class JobLogsRepository
{
    public function findByJobId(int $jobId): array
    {
        return JobLogRecord::find()
            ->where(['jobId' => $jobId])
            ->orderBy(['id' => SORT_DESC])
            ->all();
    }

    public function create(int $jobId, ?int $userId, string $summary): bool
    {
        $jobLogRecord = new JobLogRecord();

        $jobLogRecord->setAttributes([
            'jobId' => $jobId,
            'userId' => $userId,
            'summary' => $summary,
        ], false);

        $jobLogRecord->save();

        return true;
    }
}
