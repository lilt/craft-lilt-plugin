<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories;

use lilthq\craftliltplugin\records\SettingRecord;

class SettingsRepository
{
    public const ENABLE_ENTRIES_FOR_TARGET_SITES = 'enable_entries_for_target_sites';
    public const COPY_ENTRIES_SLUG_FROM_SOURCE_TO_TARGET = 'copy_entries_slug_from_source_to_target';

    public const QUEUE_EACH_TRANSLATION_FILE_SEPARATELY = 'queue_each_translation_file_separately';

    public function saveLiltApiConnectionConfiguration(string $connectorApiUrl, string $connectorApiKey): void
    {
        # connectorApiKey
        $connectorApiKeyRecord = SettingRecord::findOne(['name' => 'connector_api_key']);
        if (!$connectorApiKeyRecord) {
            $connectorApiKeyRecord = new SettingRecord(['name' => 'connector_api_key']);
        }

        $connectorApiKeyRecord->value = $connectorApiKey;
        $connectorApiKeyRecord->save();

        # connectorApiUrl
        $connectorApiUrlRecord = SettingRecord::findOne(['name' => 'connector_api_url']);
        if (!$connectorApiUrlRecord) {
            $connectorApiUrlRecord = new SettingRecord(['name' => 'connector_api_url']);
        }
        $connectorApiUrlRecord->value = $connectorApiUrl;
        $connectorApiUrlRecord->save();
    }

    public function isQueueEachTranslationFileSeparately(): bool
    {
        $settingValue = SettingRecord::findOne(
            ['name' => SettingsRepository::QUEUE_EACH_TRANSLATION_FILE_SEPARATELY]
        );

        if (empty($settingValue) || empty($settingValue->value)) {
            return false;
        }

        return (bool)$settingValue->value;
    }

    public function save(string $name, string $value): bool
    {
        $settingRecord = SettingRecord::findOne(['name' => $name]);
        if (!$settingRecord) {
            $settingRecord = new SettingRecord(['name' => $name]);
        }

        $settingRecord->value = $value;
        return $settingRecord->save();
    }
}
