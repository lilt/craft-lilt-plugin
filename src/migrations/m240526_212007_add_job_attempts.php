<?php

namespace lilthq\craftliltplugin\migrations;

use craft\db\Migration;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

/**
 * m240526_212007_add_job_attempts migration.
 */
// @codingStandardsIgnoreStart
class m240526_212007_add_job_attempts extends Migration
// @codingStandardsIgnoreEnd
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Add the 'attempt' column to the 'lilt_jobs' table
        $this->addColumn(
            CraftliltpluginParameters::JOB_TABLE_NAME,
            'attempt',
            $this->integer()->unsigned()->notNull()->defaultValue(0)
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        // Remove the 'attempt' column from the 'lilt_jobs' table
        $this->dropColumn(
            CraftliltpluginParameters::JOB_TABLE_NAME,
            'attempt'
        );
    }
}
