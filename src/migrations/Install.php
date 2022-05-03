<?php
/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\migrations;

use craft\db\Migration;

class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%lilt_jobs}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string()->null(),
            'liltJobId' => $this->integer()->null(),
            'status' => $this->string(50),
            'elementIds' => $this->json(),
            'sourceSiteId' => $this->integer()->unsigned()->notNull(),
            'sourceSiteLanguage' => $this->string(50),
            'targetSiteIds' => $this->json(),
            'files' => $this->json(),
            'dueDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createTable('{{%lilt_jobs_elements}}', [
            'jobId' => $this->integer()->null(),
            'elementId' => $this->integer()->null(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        #$this->dropTable('{{%lilt_jobs}}');
        #$this->dropTable('{{%lilt_jobs_elements}}');
    }
}
