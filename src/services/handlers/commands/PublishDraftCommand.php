<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers\commands;

class PublishDraftCommand
{
    /**
     * @var int
     */
    private $draftId;

    /**
     * @var int
     */
    private $targetSiteId;

    /**
     * @var int
     */
    private $jobId;

    /**
     * @var int
     */
    private $translationId;

    public function __construct(
        int $draftId,
        int $targetSiteId,
        int $jobId,
        int $translationId
    ) {
        $this->draftId = $draftId;
        $this->targetSiteId = $targetSiteId;
        $this->jobId = $jobId;
        $this->translationId = $translationId;
    }

    public function getDraftId(): int
    {
        return $this->draftId;
    }

    public function getTargetSiteId(): int
    {
        return $this->targetSiteId;
    }

    public function getJobId(): int
    {
        return $this->jobId;
    }

    public function getTranslationId(): int
    {
        return $this->translationId;
    }
}
