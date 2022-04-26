<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repository\external;

use GuzzleHttp\Client;
use LiltConnectorSDK\Api\JobsApi;
use LiltConnectorSDK\Configuration;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\Craftliltplugin;

class LiltJobRepository extends AbstractRepository
{
    /**
     * @throws \LiltConnectorSDK\ApiException
     */
    public function create(
        string $projectNameTemplate = 'Test project **today**',
        string $liltTranslationWorkflow = SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
        string $projectPrefix = 'prefix'
    ): JobResponse {
        $settings_response = new SettingsResponse();
        $settings_response->setProjectNameTemplate($projectNameTemplate);
        $settings_response->setLiltTranslationWorkflow($liltTranslationWorkflow);
        $settings_response->setProjectPrefix($projectPrefix);

        return $this->apiInstance->servicesApiJobsCreateJob($settings_response);
    }
}