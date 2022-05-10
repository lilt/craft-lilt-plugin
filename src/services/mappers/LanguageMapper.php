<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\mappers;

use Craft;

class LanguageMapper
{
    public $availableSites = [];

    public $languageToSiteId = [];
    public $siteIdToLanguage = [];
    public $siteIdToHandle = [];

    public function init(): void
    {
        if (!empty($this->availableSites)) {
            return;
        }

        $this->availableSites = Craft::$app->getSites()->getAllSites();

        foreach ($this->availableSites as $availableSite) {
            $this->languageToSiteId[$availableSite->language] = $availableSite->id;
            $this->siteIdToLanguage[$availableSite->id] = $availableSite->language;
            $this->siteIdToHandle[$availableSite->id] = $availableSite->handle;
        }
    }

    public function getAvailableSitesForFormField(): array
    {
        if (empty($this->availableSites)) {
            $this->init();
        }

        $result = [];
        foreach ($this->availableSites as $site) {
            $result[] = [
                'value' => $site->id,
                'label' => $site->name . '(' . $site->language . ')'
            ];
        }

        return $result;
    }

    public function getLanguageToSiteId(): array
    {
        if (empty($this->availableSites)) {
            $this->init();
        }

        return $this->languageToSiteId;
    }

    public function getSiteIdToLanguage(): array
    {
        if (empty($this->availableSites)) {
            $this->init();
        }

        return $this->siteIdToLanguage;
    }

    /**
     * @return array
     */
    public function getAvailableSites(): array
    {
        if (empty($this->availableSites)) {
            $this->init();
        }

        return $this->availableSites;
    }

    public function getLanguageBySiteId(int $siteId): ?string
    {
        if (empty($this->availableSites)) {
            $this->init();
        }

        return $this->siteIdToLanguage[$siteId] ?? null;
    }

    public function getHandleBySiteId(int $siteId): ?string
    {
        if (empty($this->availableSites)) {
            $this->init();
        }

        return $this->siteIdToHandle[$siteId] ?? null;
    }

    public function getSiteIdByLanguage(string $language): ?int
    {
        if (empty($this->availableSites)) {
            $this->init();
        }

        return $this->languageToSiteId[$language] ?? null;
    }

    public function getLanguagesBySiteIds(array $siteIds): array
    {
        if (empty($siteIds)) {
            return [];
        }

        return array_values(
            array_map(function (int $targetSiteIds) {
                return $this->getLanguageBySiteId($targetSiteIds);
            }, $siteIds)
        );
    }

    public function getSiteIdsByLanguages(array $languages): array
    {
        if (empty($languages)) {
            return [];
        }

        return array_values(
            array_map(function (string $language) {
                return $this->getSiteIdByLanguage($language);
            }, $languages)
        );
    }
}
