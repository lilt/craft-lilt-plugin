<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\job;

use craft\test\Craft;
use lilthq\craftliltplugin\Craftliltplugin;

class CreateJob
{
    private $title;
    private $entries;
    private $targetSitesIds;
    private $targetLanguages;

    public function __construct(
        string $title,
        array $entries,
        array $targetSitesIds
    ) {
        $this->title = $title;
        $this->entries = $entries;
        $this->targetSitesIds = $targetSitesIds;

        $this->targetLanguages = Craftliltplugin::getInstance()->languageMapper->getLanguagesBySiteIds(
            $this->targetSitesIds
        );
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

    public function getTargetLanguages(): array
    {
        return $this->targetLanguages;
    }
}