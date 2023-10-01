<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\migrations;

use craft\db\Migration;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\SettingRecord;

// @codingStandardsIgnoreStart
class m231001_174918_enable_split_job_file_upload extends Migration
// @codingStandardsIgnoreEnd
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $settingRecord = SettingRecord::findOne(
            ['name' => CraftliltpluginParameters::SETTING_SPLIT_JOB_FILE_UPLOAD]
        );
        if (!$settingRecord) {
            $settingRecord = new SettingRecord(
                ['name' => CraftliltpluginParameters::SETTING_SPLIT_JOB_FILE_UPLOAD]
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
            ['name' => CraftliltpluginParameters::SETTING_SPLIT_JOB_FILE_UPLOAD]
        );
        if (!$settingRecord) {
            $settingRecord = new SettingRecord(
                ['name' => CraftliltpluginParameters::SETTING_SPLIT_JOB_FILE_UPLOAD]
            );
        }

        $settingRecord->value = 0;
        return $settingRecord->save();
    }
}
