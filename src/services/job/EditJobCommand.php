<?php
/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\job;

use DateTimeInterface;

class EditJobCommand
{
    private $jobId;
    private $title;
    private $entries;
    private $sourceSiteId;
    private $targetSitesIds;
    private $dueDate;

    public function __construct(
        int $jobId,
        string $title,
        array $entries,
        array $targetSitesIds,
        int $sourceSiteId,
        DateTimeInterface $dueDate
    ) {
        $this->jobId = $jobId;
        $this->title = $title;
        $this->entries = $entries;
        $this->targetSitesIds = $targetSitesIds;
        $this->sourceSiteId = $sourceSiteId;
        $this->dueDate = $dueDate;

        //Remove source site from target site if it is there
        if (in_array($this->sourceSiteId, $this->targetSitesIds, true)) {
            $this->targetSitesIds = array_diff($this->targetSitesIds, [$this->sourceSiteId]);
        }
    }

    public function getJobId(): int
    {
        return $this->jobId;
    }

    public function getDueDate(): DateTimeInterface
    {
        return $this->dueDate;
    }

    public function getSourceSiteId(): int
    {
        return $this->sourceSiteId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getEntries(): array
    {
        return $this->entries;
    }

    public function getTargetSitesIds(): array
    {
        return $this->targetSitesIds;
    }
}