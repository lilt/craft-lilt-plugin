<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\migrations;

use craft\db\Migration;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): void
    {
        $this->createTable(CraftliltpluginParameters::JOB_TABLE_NAME, [
            'id' => $this->primaryKey()->unsigned(),
            'title' => $this->string()->null(),
            'liltJobId' => $this->integer()->null(),
            'status' => $this->string(50),
            'elementIds' => $this->json(),
            'sourceSiteId' => $this->integer()->unsigned()->notNull(),
            'sourceSiteLanguage' => $this->string(50),
            'targetSiteIds' => $this->json(),
            'dueDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createTable('{{%lilt_translations}}', [
            'id' => $this->primaryKey()->unsigned(),
            'uid' => $this->uid(),
            'jobId' => $this->integer()->unsigned()->null(),
            'elementId' => $this->integer()->unsigned()->null(),
            'draftId' => $this->integer()->unsigned()->null(),
            'sourceSiteId' => $this->integer()->null(),
            'targetSiteId' => $this->integer()->null(),
            'sourceContent' => $this->json(),
            'targetContent' => $this->json(),
            'lastDelivery' => $this->integer()->null(),
            'status' => $this->string(50)->null(),
            'connectorTranslationId' => $this->integer()->unsigned()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(
            null,
            CraftliltpluginParameters::TRANSLATION_TABLE_NAME,
            ['jobId','elementId','targetSiteId'],
            true
        );

        $this->addForeignKey(
            null,
            CraftliltpluginParameters::TRANSLATION_TABLE_NAME,
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
    public function safeDown(): void
    {
        $this->dropTableIfExists(CraftliltpluginParameters::TRANSLATION_TABLE_NAME);
        $this->dropTableIfExists(CraftliltpluginParameters::JOB_TABLE_NAME);
    }
}
