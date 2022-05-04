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
        string $projectNameTemplate = 'CraftCMS | {today}',
        string $liltTranslationWorkflow = SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
        string $projectPrefix = ''
    ): JobResponse {
        $settings_response = new SettingsResponse();
        $settings_response->setProjectNameTemplate($projectNameTemplate);
        $settings_response->setLiltTranslationWorkflow($liltTranslationWorkflow);
        $settings_response->setProjectPrefix($projectPrefix);

        return $this->apiInstance->servicesApiJobsCreateJob($settings_response);
    }

    public function start(int $jobId): bool
    {
        try {
            $this->apiInstance->servicesApiJobsStartJob($jobId);
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
    public function findOneById(int $jobId): JobResponse
    {
        return $this->apiInstance->servicesApiJobsGetJobById($jobId);
    }
}
