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

    private $fieldValue;

    public function __construct(bool $applied, array $i18nRecords = [], $fieldValue = null)
    {
        $this->applied = $applied;
        $this->i18nRecords = $i18nRecords;
        $this->fieldValue = $fieldValue;
    }

    /**
     * @return mixed
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }

    public static function applied(array $i18nRecords = [], $field = null): self
    {
        return new self(true, $i18nRecords, $field);
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
