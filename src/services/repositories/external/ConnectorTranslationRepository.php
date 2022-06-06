<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories\external;

use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse1 as ConnectorTranslationsResponse;
use LiltConnectorSDK\Model\TranslationResponse;

class ConnectorTranslationRepository extends AbstractConnectorExternalRepository
{
    /**
     * @throws ApiException
     */
    public function findByJobId(int $jobId): ConnectorTranslationsResponse
    {
        return $this->apiInstance->servicesApiDeliveriesGetDeliveriesByJobId(
            100,
            "00",
            $jobId
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
        $regExpr = '/\d+_element_(\d+).json\+html/';
        preg_match($regExpr, $translationResponse->getName(), $matches);

        if (!isset($matches[1])) {
            throw new \RuntimeException('Cant find element id from translation name');
        }

        return (int)$matches[1];
    }
}
