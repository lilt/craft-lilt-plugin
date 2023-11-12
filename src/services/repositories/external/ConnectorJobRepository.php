<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories\external;

use Craft;
use Exception;
use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\SettingsResponse;
use LiltConnectorSDK\ObjectSerializer;

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

    /**
     * @throws ApiException
     */
    public function start(int $liltJobId): bool
    {
        try {
            $this->apiInstance->servicesApiJobsStartJob($liltJobId);
        } catch (ApiException $ex) {
            Craft::warning([
                'message' => sprintf(
                    'Communication exception when calling JobsApi->servicesApiJobsAddFile: %s',
                    $ex->getMessage()
                ),
                'exception_message' => $ex->getMessage(),
                'exception_trace' => $ex->getTrace(),
                'exception' => $ex,
            ]);

            if ($ex->getCode() === 500) {
                throw $ex;
            }

            return false;
        } catch (Exception $ex) {
            Craft::error([
                'message' => sprintf('Exception when calling JobsApi->servicesApiJobsAddFile: %s', $ex->getMessage()),
                'exception_message' => $ex->getMessage(),
                'exception_trace' => $ex->getTrace(),
                'exception' => $ex,
            ]);

            return false;
        }

        return true;
    }

    /**
     * @throws ApiException
     */
    public function findOneById(int $liltJobId): JobResponse
    {
        $cacheKey = __METHOD__ . ':' . $liltJobId;

        try {
            $data = Craft::$app->cache->get($cacheKey);

            if ($data) {
                /**
                 * @var JobResponse $response
                 */
                $response = ObjectSerializer::deserialize($data, JobResponse::class);

                return $response;
            }
        } catch (Exception $ex) {
            Craft::error([
                "message" => sprintf(
                    'Deserialize error for lilt job %d: %s ',
                    $liltJobId,
                    $ex->getMessage()
                ),
                "FILE" => __FILE__,
                "LINE" => __LINE__,
            ]);
        }

        $response = $this->apiInstance->servicesApiJobsGetJobById($liltJobId);

        $data = $response->__toString();

        Craft::$app->cache->add(
            $cacheKey,
            $data,
            10
        );

        return $response;
    }
}
