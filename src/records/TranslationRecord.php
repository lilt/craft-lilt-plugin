<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\records;

use craft\db\ActiveRecord;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class TranslationRecord extends ActiveRecord
{
    public const STATUS_NEW = 'new';

    public const STATUS_READY_FOR_REVIEW = 'ready-for-review';
    public const STATUS_READY_TO_PUBLISH = 'ready-to-publish';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return CraftliltpluginParameters::TRANSLATION_TABLE_NAME;
    }
}
