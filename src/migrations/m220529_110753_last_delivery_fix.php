<?php

namespace lilthq\craftliltplugin\migrations;

use craft\db\Migration;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

/**
 * m220529_110753_last_delivery_fix migration.
 */
class m220529_110753_last_delivery_fix extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->update(
            CraftliltpluginParameters::TRANSLATION_TABLE_NAME,
            ['lastDelivery' => null]
        );

        $this->alterColumn(
            CraftliltpluginParameters::TRANSLATION_TABLE_NAME,
            'lastDelivery',
            $this->dateTime()->null()
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->alterColumn(
            CraftliltpluginParameters::TRANSLATION_TABLE_NAME,
            'lastDelivery',
            $this->integer()->null()
        );
    }
}
