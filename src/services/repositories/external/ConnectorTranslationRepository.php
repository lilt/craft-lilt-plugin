<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories\external;

use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse1 as ConnectorTranslationsResponse;

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
}