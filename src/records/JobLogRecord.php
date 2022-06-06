<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\records;

use craft\db\ActiveRecord;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class JobLogRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return CraftliltpluginParameters::JOB_LOGS_TABLE_NAME;
    }
}
