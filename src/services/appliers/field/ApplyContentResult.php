<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use lilthq\craftliltplugin\records\I18NRecord;

class ApplyContentResult
{
    /**
     * @var I18NRecord[]
     */
    private $i18nRecords;

    /**
     * @var bool
     */
    private $applied;

    public function __construct(bool $applied, array $i18nRecords = [])
    {
        $this->applied = $applied;
        $this->i18nRecords = $i18nRecords;
    }

    public static function applied(array $i18nRecords = []): self
    {
        return new self(true, $i18nRecords);
    }

    public static function fail(): self
    {
        return new self(false);
    }

    /**
     * @return bool
     */
    public function isApplied(): bool
    {
        return $this->applied;
    }

    /**
     * @return I18NRecord[]
     */
    public function getI18nRecords(): array
    {
        return $this->i18nRecords;
    }
}
