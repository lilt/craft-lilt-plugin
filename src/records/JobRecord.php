<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\records;

use craft\db\ActiveRecord;
use craft\gql\types\elements\Element;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id ID
 * @property string $title [varchar(255)]
 * @property int $liltJobId [int(11)]
 * @property string $status [varchar(50)]
 * @property string $elementIds [json]
 * @property string $versions [json]
 * @property int $sourceSiteId [int(11) unsigned]
 * @property int $sourceSiteLanguage [int(11) unsigned]
 * @property string $targetSiteIds [json]
 * @property string $dueDate [datetime]
 *
 * @property-read ActiveQueryInterface $element
 * @property string $translationWorkflow [varchar(50)]
 */
class JobRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return CraftliltpluginParameters::JOB_TABLE_NAME;
    }

    /**
     * Returns the entry’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function isInstantFlow(): bool
    {
        return strtolower($this->translationWorkflow) === strtolower(
            SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT
        );
    }

    public function isVerifiedFlow(): bool
    {
        return strtolower($this->translationWorkflow) === strtolower(
            SettingsResponse::LILT_TRANSLATION_WORKFLOW_VERIFIED
        );
    }
}
