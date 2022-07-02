<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\elements\db;

use craft\elements\db\ElementQuery;
use lilthq\craftliltplugin\datetime\DateTime;
use lilthq\craftliltplugin\elements\Job;

class JobQuery extends ElementQuery
{
    public $liltJobId;
    public string|array|null $status = null;
    public $sourceSite;
    public $dueDate;

    public mixed $dateCreated = null;
    public mixed $dateUpdated = null;

    public function status($value): self
    {
        $this->status = $value;

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function afterPopulate(array $elements): array
    {
        return array_map(
            static function (Job $element) {
                if (!empty($element->dueDate)) {
                    $element->dueDate = new DateTime($element->dueDate);
                }
                if (!empty($element->liltJobId)) {
                    $element->liltJobId = (int)$element->liltJobId;
                }
                if (!empty($element->authorId)) {
                    $element->authorId = (int)$element->authorId;
                }
                if (!empty($element->sourceSiteId)) {
                    $element->sourceSiteId = (int)$element->sourceSiteId;
                }
                return $element;
            },
            $elements
        );
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('lilt_jobs');

        $this->query->select([
            'lilt_jobs.title',
            'lilt_jobs.authorId',
            'lilt_jobs.liltJobId',
            'lilt_jobs.status',
            'lilt_jobs.sourceSiteId',
            'lilt_jobs.sourceSiteLanguage',
            'lilt_jobs.targetSiteIds',
            'lilt_jobs.elementIds',
            'lilt_jobs.versions',
            'lilt_jobs.translationWorkflow',
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
