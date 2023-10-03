<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\migrations;

use craft\db\Migration;
use lilthq\craftliltplugin\records\SettingRecord;
use lilthq\craftliltplugin\services\repositories\SettingsRepository;

// @codingStandardsIgnoreStart
class m231001_174918_queue_each_translation_file_separately extends Migration
// @codingStandardsIgnoreEnd
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $settingRecord = SettingRecord::findOne(
            ['name' => SettingsRepository::QUEUE_EACH_TRANSLATION_FILE_SEPARATELY]
        );
        if (!$settingRecord) {
            $settingRecord = new SettingRecord(
                ['name' => SettingsRepository::QUEUE_EACH_TRANSLATION_FILE_SEPARATELY]
            );
        }

        $settingRecord->value = 1;
        return $settingRecord->save();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $settingRecord = SettingRecord::findOne(
            ['name' => SettingsRepository::QUEUE_EACH_TRANSLATION_FILE_SEPARATELY]
        );
        if (!$settingRecord) {
            $settingRecord = new SettingRecord(
                ['name' => SettingsRepository::QUEUE_EACH_TRANSLATION_FILE_SEPARATELY]
            );
        }

        $settingRecord->value = 0;
        return $settingRecord->save();
    }
}
