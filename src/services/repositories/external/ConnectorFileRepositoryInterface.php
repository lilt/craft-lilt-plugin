<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories\external;

use DateTimeInterface;

interface ConnectorFileRepositoryInterface
{
    public function addFileToJob(
        int $jobId,
        string $fileName,
        string $filePath,
        string $sourceLanguage,
        array $targetLanguages,
        ?DateTimeInterface $dueDate
    ): bool;
}
