<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\migrations;

use craft\db\Migration;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

// @codingStandardsIgnoreStart
class m220602_153515_add_author_id extends Migration
// @codingStandardsIgnoreEnd
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(
            CraftliltpluginParameters::JOB_TABLE_NAME,
            'authorId',
            $this->integer()->null()
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn(
            CraftliltpluginParameters::JOB_TABLE_NAME,
            'authorId'
        );
    }
}
