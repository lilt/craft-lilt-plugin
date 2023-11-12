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

class ConnectorTranslationRepository extends AbstractConnectorExternalRepository
{
    /**
     * @throws ApiException
     */
    public function findByJobId(int $jobId): ConnectorTranslationsResponse
    {
        $cacheKey = __METHOD__ . ':' . $jobId;

        try {
            $data = Craft::$app->cache->get($cacheKey);

            if ($data) {
                /**
                 * @var ConnectorTranslationsResponse $response
                 */
                $response = ObjectSerializer::deserialize($data, ConnectorTranslationsResponse::class);

                return $response;
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

        $response = $this->apiInstance->servicesApiDeliveriesGetDeliveriesByJobId(
            100,
            "00",
            $jobId
        );

        $data = $response->__toString();

        Craft::$app->cache->add(
            $cacheKey,
            $data,
            10
        );

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
}
