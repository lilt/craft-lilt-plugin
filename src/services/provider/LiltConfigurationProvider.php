<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\provider;

use LiltConnectorSDK\Configuration;
use lilthq\craftliltplugin\Craftliltplugin;

class LiltConfigurationProvider
{
    public function provide(): Configuration
    {
        $config = Configuration::getDefaultConfiguration();

        $config->setAccessToken(
            Craftliltplugin::getInstance()->getConnectorKey()
        );

        $config->setUserAgent('lilthq/craft-lilt-plugin:1.0.0');

        return $config;
    }
}