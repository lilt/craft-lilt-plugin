<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers\commands;

use craft\base\ElementInterface;

class CreateDraftCommand
{
    private $element;
    private $jobTitle;
    private $sourceSiteId;
    private $targetSiteId;
    private $flow;
    private $authorId;

    public function __construct(
        ElementInterface $element,
        string $jobTitle,
        int $sourceSiteId,
        int $targetSiteId,
        string $flow,
        int $authorId
    ) {
        $this->element = $element;
        $this->jobTitle = $jobTitle;
        $this->sourceSiteId = $sourceSiteId;
        $this->targetSiteId = $targetSiteId;
        $this->flow = $flow;
        $this->authorId = $authorId;
    }

    public function getElement(): ElementInterface
    {
        return $this->element;
    }

    public function getJobTitle(): string
    {
        return $this->jobTitle;
    }

    public function getSourceSiteId(): int
    {
        return $this->sourceSiteId;
    }

    public function getTargetSiteId(): int
    {
        return $this->targetSiteId;
    }

    public function getFlow(): string
    {
        return $this->flow;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }
}
