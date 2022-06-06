<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\records;

use craft\db\ActiveRecord;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class I18NRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return CraftliltpluginParameters::I18N_TABLE_NAME;
    }
}
