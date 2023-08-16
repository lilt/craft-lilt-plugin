<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers\commands;

use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\TranslationRecord;

class SyncFromLiltCommand
{
    /**
     * @var Job
     */
    private $job;

    /**
     * @var TranslationRecord
     */
    private $translation;

    /**
     * @var TranslationResponse
     */
    private $translationResponse;
}
