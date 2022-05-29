<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\migrations;

use craft\db\Migration;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

/**
 * m220529_084426_activity_log migration.
 */
class m220529_084426_activity_log extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable(CraftliltpluginParameters::JOB_LOGS_TABLE_NAME, [
            'id' => $this->primaryKey()->unsigned(),
            'uid' => $this->uid(),
            'jobId' => $this->integer()->unsigned()->null(),
            'userId' => $this->integer()->unsigned()->null(),
            'summary' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);

        $this->addForeignKey(
            null,
            CraftliltpluginParameters::JOB_LOGS_TABLE_NAME,
            ['jobId'],
            CraftliltpluginParameters::JOB_TABLE_NAME,
            ['id'],
            'CASCADE',
            null
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists(CraftliltpluginParameters::JOB_LOGS_TABLE_NAME);
    }
}
