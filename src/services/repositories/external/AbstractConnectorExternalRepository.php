<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories\external;

use Craft;
use Exception;
use LiltConnectorSDK\Api\JobsApi;
use LiltConnectorSDK\Api\TranslationsApi;
use LiltConnectorSDK\ApiException;

class AbstractConnectorExternalRepository
{
    /**
     * @var JobsApi|TranslationsApi
     */
    public $apiInstance;

    /**
     * @throws ApiException
     */
    protected function handleException(Exception $ex): void
    {
        if ($ex instanceof ApiException) {
            Craft::warning([
                'message' => sprintf(
                    'Communication exception when calling JobsApi->servicesApiJobsAddFile: %s',
                    $ex->getMessage()
                ),
                'exception_message' => $ex->getMessage(),
                'exception_trace' => $ex->getTrace(),
                'exception' => $ex,
            ]);

            if ($ex->getCode() >= 500 && $ex->getCode() < 600) {
                throw $ex;
            }
        }
    }
}
