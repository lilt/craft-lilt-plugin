<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers;

use LiltConnectorSDK\Configuration;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\records\SettingRecord;

class ConnectorConfigurationProvider
{
    public function provide(): Configuration
    {
        $config = Configuration::getDefaultConfiguration();

        $config->setAccessToken(
            Craftliltplugin::getInstance()->getConnectorKey()
        );

        try {
            $connectorApiUrlRecord = SettingRecord::findOne(['name' => 'connector_api_url']);
        } catch (\Exception $ex) {
            //TODO: Can be called before migrations? Table not found from tests, research needed here
            $connectorApiUrlRecord = null;
        }

        if ($connectorApiUrlRecord) {
            $config->setHost($connectorApiUrlRecord->value);
        }

        if (!$connectorApiUrlRecord) {
            $connectorApiUrl = getenv('CRAFT_LILT_PLUGIN_CONNECTOR_API_URL');
            if ($connectorApiUrl) {
                $config->setHost($connectorApiUrl);
            }
        }

        $config->setUserAgent('lilthq/craft-lilt-plugin:1.0.0');

        return $config;
    }
}
