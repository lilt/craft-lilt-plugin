<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories;

use lilthq\craftliltplugin\records\I18NRecord;

class I18NRepository
{
    public function findAllByTargetSiteId(int $targetSiteId): array
    {
        return I18NRecord::findAll(['targetSiteId' => $targetSiteId]);
    }

    public function new(int $sourceSiteId, int $targetSiteId, string $sourceValue, string $targetValue): I18NRecord
    {
        $record = new I18NRecord();

        $record->target = $targetValue;
        $record->source = $sourceValue;
        $record->sourceSiteId = $sourceSiteId;
        $record->targetSiteId = $targetSiteId;

        return $record;
    }
}
