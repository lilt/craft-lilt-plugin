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
            'versions' => $this->json(), //contains which version of element should be translated, empty if current
            'translationWorkflow' => $this->string(50),
            'sourceSiteId' => $this->integer()->unsigned()->notNull(),
            'sourceSiteLanguage' => $this->string(50),
            'targetSiteIds' => $this->json(),
            'dueDate' => $this->dateTime()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createTable(CraftliltpluginParameters::TRANSLATION_TABLE_NAME, [
            'id' => $this->primaryKey()->unsigned(),
            'uid' => $this->uid(),
            'jobId' => $this->integer()->unsigned()->null(),
            'elementId' => $this->integer()->unsigned()->null(),
            'versionId' => $this->integer()->unsigned()->null(),
            'translatedDraftId' => $this->integer()->unsigned()->null(),
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

        $this->createTable(CraftliltpluginParameters::I18N_TABLE_NAME, [
            'id' => $this->primaryKey()->unsigned(),
            'uid' => $this->uid(),
            'sourceSiteId' => $this->integer()->unsigned()->null(),
            'targetSiteId' => $this->integer()->unsigned()->null(),
            'source' => $this->text(),
            'target' => $this->text(),
            'hash' => $this->string(32),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(
            null,
            CraftliltpluginParameters::I18N_TABLE_NAME,
            ['sourceSiteId', 'targetSiteId', 'hash'],
            true
        );

        $this->createIndex(
            null,
            CraftliltpluginParameters::TRANSLATION_TABLE_NAME,
            ['jobId','elementId','sourceSiteId','targetSiteId'],
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
        $this->dropTableIfExists(CraftliltpluginParameters::I18N_TABLE_NAME);
    }
}
