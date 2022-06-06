<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories;

use lilthq\craftliltplugin\records\I18NRecord;

class I18NRepository
{
    public function findAllByTargetSiteId(int $targetSiteId): array
    {
        return I18NRecord::findAll(['targetSiteId' => $targetSiteId]);
    }
}
