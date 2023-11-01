<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories\external;

use Craft;
use DateTimeInterface;
use Exception;
use LiltConnectorSDK\ApiException;

class ConnectorFileRepository extends AbstractConnectorExternalRepository implements ConnectorFileRepositoryInterface
{
    /**
     * @throws ApiException
     */
    public function addFileToJob(
        int $jobId,
        string $fileName,
        string $filePath,
        string $sourceLanguage,
        array $targetLanguages,
        ?DateTimeInterface $dueDate
    ): bool {
        try {
            $this->apiInstance->servicesApiJobsAddFile(
                $jobId,
                $fileName,
                $sourceLanguage,
                $targetLanguages,
                $dueDate,
                $filePath
            );
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
                'message' => sprintf(
                    'Exception when calling JobsApi->servicesApiJobsAddFile: %s',
                    $ex->getMessage()
                ),
                'exception_message' => $ex->getMessage(),
                'exception_trace' => $ex->getTrace(),
                'exception' => $ex,
            ]);

            return false;
        }

        return true;
    }
}
