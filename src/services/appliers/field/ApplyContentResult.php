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
    private $reason;

    public const REASON_UNKNOWN = "unknown";
    public const REASON_CHAR_LIMIT = "char_limit";

    public function __construct(bool $applied, array $i18nRecords = [], $fieldValue = null, string $reason = self::REASON_UNKNOWN)
    {
        $this->applied = $applied;
        $this->reason = $reason;
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
    public static function charLimit(): self
    {
        return new self(false, [], null, self::REASON_CHAR_LIMIT);
    }

    /**
     * @return bool
     */
    public function isApplied(): bool
    {
        return $this->applied;
    }
    public function isCharLimitReached(): bool
    {
        return $this->reason === self::REASON_CHAR_LIMIT;
    }

    /**
     * @return I18NRecord[]
     */
    public function getI18nRecords(): array
    {
        return $this->i18nRecords;
    }
}
