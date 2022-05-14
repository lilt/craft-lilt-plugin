<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories\external;

use LiltConnectorSDK\Api\JobsApi;
use LiltConnectorSDK\Api\TranslationsApi;

class AbstractRepository
{
    /**
     * @var JobsApi|TranslationsApi
     */
    public $apiInstance;
}
