<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories\external;

use Craft;
use Exception;
use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse1 as ConnectorTranslationsResponse;
use LiltConnectorSDK\Model\TranslationResponse;
use LiltConnectorSDK\ObjectSerializer;
use lilthq\craftliltplugin\exceptions\WrongTranslationFilenameException;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class ConnectorTranslationRepository extends AbstractConnectorExternalRepository
{
    /**
     * @throws ApiException
     */
    public function findByJobId(int $jobId): ConnectorTranslationsResponse
    {
        $cacheKey = __METHOD__ . ':' . $jobId;
        $cache = CraftliltpluginParameters::getResponseCache();

        if (!empty($cache)) {
            $response = $this->getResponseFromCache($cacheKey, $jobId);

            if (!empty($response)) {
                return $response;
            }
        }

        $response = $this->apiInstance->servicesApiDeliveriesGetDeliveriesByJobId(
            1000,
            "00",
            $jobId
        );

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
     * @throws ApiException
     */
    public function findById(int $translationId): TranslationResponse
    {
        return $this->apiInstance->servicesApiDeliveriesGetDeliveryById(
            $translationId
        );
    }

    /**
     * @throws ApiException
     */
    public function findTranslationContentById(int $translationId): string
    {
        return $this->apiInstance->servicesApiDeliveriesDownloadDelivery(
            $translationId
        );
    }

    public function getElementIdFromTranslationResponse(TranslationResponse $translationResponse): int
    {
        $regExpr = '/\d+_element_(\d+)(_.*|)\.json\+html/';
        preg_match($regExpr, $translationResponse->getName(), $matches);

        if (!isset($matches[1])) {
            throw new WrongTranslationFilenameException('Cant find element id from translation name');
        }

        return (int) $matches[1];
    }

    /**
     * @param string $cacheKey
     * @param int $jobId
     * @return ConnectorTranslationsResponse|null
     */
    private function getResponseFromCache(string $cacheKey, int $jobId): ?ConnectorTranslationsResponse
    {
        $response = null;

        try {
            $dataFromCache = Craft::$app->cache->get($cacheKey);

            if ($dataFromCache) {
                /**
                 * @var ConnectorTranslationsResponse $response
                 */
                $response = ObjectSerializer::deserialize($dataFromCache, ConnectorTranslationsResponse::class);
            }
        } catch (Exception $ex) {
            Craft::error([
                "message" => sprintf(
                    'Deserialize error for lilt job %d: %s ',
                    $jobId,
                    $ex->getMessage()
                ),
                "FILE" => __FILE__,
                "LINE" => __LINE__,
            ]);
        }

        return $response;
    }
}
