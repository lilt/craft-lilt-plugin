<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\records;

use craft\db\ActiveRecord;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

/**
 * @property mixed|null $target
 * @property int $id [int(10) unsigned]
 * @property int $sourceSiteId [int(11) unsigned]
 * @property int $targetSiteId [int(11) unsigned]
 * @property string $source
 * @property string $hash [varchar(32)]
 */
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
