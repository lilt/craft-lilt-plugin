<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repository\external;

use LiltConnectorSDK\Api\JobsApi;
use LiltConnectorSDK\Api\TranslationsApi;

class AbstractRepository
{
    /**
     * @var JobsApi|TranslationsApi
     */
    public $apiInstance;
}