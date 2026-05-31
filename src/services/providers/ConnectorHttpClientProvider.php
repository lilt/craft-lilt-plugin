<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers;

use GuzzleHttp\Client;
use lilthq\craftliltplugin\Craftliltplugin;

class ConnectorHttpClientProvider
{
    /**
     * Guzzle client used for all Lilt API calls. Carries the connector-version header as a
     * default so every request made through it is tagged with `craft/{version}`.
     */
    public function provide(): Client
    {
        return new Client([
            'headers' => [
                Craftliltplugin::CONNECTOR_VERSION_HEADER =>
                    Craftliltplugin::getInstance()->getConnectorVersion(),
            ],
        ]);
    }
}
