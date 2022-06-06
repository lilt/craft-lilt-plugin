<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories\external;

use Craft;
use Exception;
use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\SettingsResponse;

class ConnectorJobRepository extends AbstractConnectorExternalRepository
{
    /**
     * @throws ApiException
     */
    public function create(
        string $projectPrefix,
        string $liltTranslationWorkflow = SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT
    ): JobResponse {
        $settings_response = new SettingsResponse();
        $settings_response->setLiltTranslationWorkflow($liltTranslationWorkflow);
        $settings_response->setProjectPrefix($projectPrefix);

        return $this->apiInstance->servicesApiJobsCreateJob($settings_response);
    }

    public function start(int $liltJobId): bool
    {
        try {
            $this->apiInstance->servicesApiJobsStartJob($liltJobId);
        } catch (Exception $e) {
            Craft::error(
                sprintf('Exception when calling JobsApi->servicesApiJobsAddFile: %s', $e->getMessage()),
                __METHOD__
            );

            return false;
        }

        return true;
    }

    /**
     * @throws ApiException
     */
    public function findOneById(int $liltJobId): JobResponse
    {
        return $this->apiInstance->servicesApiJobsGetJobById($liltJobId);
    }
}
