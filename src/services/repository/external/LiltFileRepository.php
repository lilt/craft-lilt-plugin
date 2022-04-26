<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repository\external;

use Craft;
use DateTimeInterface;
use Exception;
use GuzzleHttp\Client;
use LiltConnectorSDK\Api\JobsApi;
use lilthq\craftliltplugin\Craftliltplugin;

class LiltFileRepository extends AbstractRepository
{
    public function addFileToJob(
        int $jobId,
        string $fileName,
        string $filePath,
        array $targetLanguages,
        DateTimeInterface $dueDate
    ): bool {
        $configuration = Craftliltplugin::getInstance()->liltConfigurationProvider->provide();

        try {
            $this->apiInstance->servicesApiJobsAddFile(
                $jobId,
                $fileName,
                $targetLanguages,
                $dueDate,
                $filePath
            );
        } catch (Exception $e) {
            Craft::error(
                sprintf('Exception when calling JobsApi->servicesApiJobsAddFile: %s', $e->getMessage()),
                __METHOD__
            );

            return false;
        }

        return true;
    }
}