<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\migrations;

use craft\db\Migration;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

// @codingStandardsIgnoreStart
class m230423_112548_create_translation_notifications extends Migration
// @codingStandardsIgnoreEnd
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropTableIfExists(CraftliltpluginParameters::TRANSLATION_NOTIFICATIONS_TABLE_NAME);

        $this->createTable(CraftliltpluginParameters::TRANSLATION_NOTIFICATIONS_TABLE_NAME, [
            'id' => $this->primaryKey()->unsigned(),
            'jobId' => $this->integer()->notNull(),
            'translationId' => $this->integer()->notNull(),
            'reason' => $this->string(64),
            'level' => $this->string(64),
            'fieldId' => $this->integer(),
            'fieldUID' => $this->char(36)->null(),
            'fieldHandle' => $this->string(64)->null(),
            'sourceContent' => $this->string(255)->null(),
            'targetContent' => $this->string(255)->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createIndex(
            null,
            CraftliltpluginParameters::TRANSLATION_NOTIFICATIONS_TABLE_NAME,
            ['jobId', 'level'],
            false
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists(CraftliltpluginParameters::TRANSLATION_NOTIFICATIONS_TABLE_NAME);

        return true;
    }
}
