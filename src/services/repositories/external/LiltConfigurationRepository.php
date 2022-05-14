<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories\external;

use LiltConnectorSDK\Configuration;
use lilthq\craftliltplugin\Craftliltplugin;

class LiltConfigurationRepository
{
    public function get(): Configuration
    {
        $config = Configuration::getDefaultConfiguration();

        $config->setAccessToken(
            Craftliltplugin::getInstance()->getConnectorKey()
        );

        $config->setUserAgent('lilthq/craft-lilt-plugin:1.0.0');

        return $config;
    }
}
