<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\records;

use craft\db\ActiveRecord;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

/**
 * @property int $translatedDraftId
 * @property int $id [int(10) unsigned]
 * @property int $jobId [int(11) unsigned]
 * @property int $elementId [int(11) unsigned]
 * @property int $versionId [int(11) unsigned]
 * @property int $sourceSiteId [int(11)]
 * @property int $targetSiteId [int(11)]
 * @property string $sourceContent [json]
 * @property string $targetContent [json]
 * @property int $lastDelivery [int(11)]
 * @property string $status [varchar(50)]
 * @property int $connectorTranslationId [int(11) unsigned]
 */
class TranslationRecord extends ActiveRecord
{
    public const STATUS_NEW = 'new';

    public const STATUS_READY_FOR_REVIEW = 'ready-for-review';
    public const STATUS_READY_TO_PUBLISH = 'ready-to-publish';
    public const STATUS_IN_PROGRESS = 'in-progress';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';
    public const STATUS_NEEDS_ATTENTION = 'needs-attention';

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return CraftliltpluginParameters::TRANSLATION_TABLE_NAME;
    }
}
