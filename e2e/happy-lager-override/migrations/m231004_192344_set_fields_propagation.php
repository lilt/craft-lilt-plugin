<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\fields\Matrix;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

/**
 * m230304_162344_set_fields_translatable migration.
 */
class m231004_192344_set_fields_propagation extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $ignoreDropdownsRecord = new \lilthq\craftliltplugin\records\SettingRecord(['name' => 'ignore_dropdowns']);
        $ignoreDropdownsRecord->value = 1;
        $ignoreDropdownsRecord->save();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m230304_162344_set_fields_translatable can't be reverted.\n";

        return true;
    }
}
