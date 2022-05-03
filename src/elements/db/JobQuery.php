<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\elements\db;

use craft\elements\db\ElementQuery;
use lilthq\craftliltplugin\elements\Job;

class JobQuery extends ElementQuery
{
    public $liltJobId;
    public $status;
    public $sourceSite;
    public $dueDate;
    public $dateCreated;
    public $dateUpdated;

    public function status($value)
    {
        $this->status = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('lilt_jobs');

        $this->query->select([
            'lilt_jobs.title',
            'lilt_jobs.files',
            'lilt_jobs.liltJobId',
            'lilt_jobs.status',
            'lilt_jobs.sourceSiteId',
            'lilt_jobs.sourceSiteLanguage',
            'lilt_jobs.targetSiteIds',
            'lilt_jobs.elementIds',
            'lilt_jobs.dueDate',
            'lilt_jobs.dateCreated',
            'lilt_jobs.dateUpdated',
        ]);

        if ($this->status) {
            if (is_array($this->status)) {
                $this->subQuery->andWhere(['in', 'lilt_jobs.status', $this->status]);
            } elseif ($this->status !== '*') {
                $this->subQuery->andWhere('lilt_jobs.status = :status', [':status' => $this->status]);
            }
        }

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status): bool
    {
        return array_key_exists($status, Job::statuses());
    }
}
