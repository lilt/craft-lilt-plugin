<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories\external;

use Craft;
use Exception;
use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\SettingsResponse;
use LiltConnectorSDK\ObjectSerializer;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

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
        $cache = CraftliltpluginParameters::getResponseCache();

        if (!empty($cache)) {
            $response = $this->getResponseFromCache($cacheKey, $liltJobId);

            if (!empty($response)) {
                return $response;
            }
        }

        $response = $this->apiInstance->servicesApiJobsGetJobById($liltJobId);

        $data = $response->__toString();

        if (!empty($cache)) {
            Craft::$app->cache->add(
                $cacheKey,
                $data,
                $cache
            );
        }

        return $response;
    }

    /**
     * @param string $cacheKey
     * @param int $liltJobId
     * @return JobResponse
     */
    private function getResponseFromCache(string $cacheKey, int $liltJobId): ?JobResponse
    {
        $response = null;

        try {
            $dataFromCache = Craft::$app->cache->get($cacheKey);

            if ($dataFromCache) {
                /**
                 * @var JobResponse $response
                 */
                $response = ObjectSerializer::deserialize($dataFromCache, JobResponse::class);
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

        return $response;
    }
}
