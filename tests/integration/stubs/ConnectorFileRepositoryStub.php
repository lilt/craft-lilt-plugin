<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\stubs;

use DateTimeInterface;
use lilthq\craftliltplugin\services\repositories\external\AbstractConnectorExternalRepository;
use lilthq\craftliltplugin\services\repositories\external\ConnectorFileRepositoryInterface;

class ConnectorFileRepositoryStub extends AbstractConnectorExternalRepository implements ConnectorFileRepositoryInterface
{
    public $files = [];
    public function addFileToJob(
        int $jobId,
        string $fileName,
        string $filePath,
        string $sourceLanguage,
        array $targetLanguages,
        ?DateTimeInterface $dueDate
    ): bool {
        $this->files[] = [
            'jobId' => $jobId,
            'fileName' => $fileName,
            'filePath' => $filePath,
            'sourceLanguage' => $sourceLanguage,
            'targetLanguages' => $targetLanguages,
            'dueDate' => $dueDate,
        ];

        return true;
    }
}
