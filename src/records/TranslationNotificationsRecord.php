<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\records;

use Craft;
use craft\db\ActiveRecord;
use craft\helpers\UrlHelper;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

/**
 * @property int $id [int(10) unsigned]
 * @property int $jobId [int(11)]
 * @property int $translationId [int(11)]
 * @property string $reason [varchar(64)]
 * @property string $level [varchar(64)]
 * @property int $fieldId [int(11)]
 * @property string $fieldUID [char(36)]
 * @property string $fieldHandle [varchar(64)]
 * @property string $sourceContent [varchar(64)]
 * @property string $targetContent [varchar(64)]
 */
class TranslationNotificationsRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return CraftliltpluginParameters::TRANSLATION_NOTIFICATIONS_TABLE_NAME;
    }

    public function getReason(): string
    {
        $reason = $this->reason;

        if (empty($this->fieldId)) {
            return $reason;
        }

        $field = Craft::$app->getFields()->getFieldById($this->fieldId);
        $fieldSettings = $field->getSettings();

        if ($reason == "reached_characters_limit" && !empty($fieldSettings['charLimit'])) {
            $editUrl = 'Please refer to the <a target="_blank" href="'
                . UrlHelper::cpUrl('settings/fields/edit/' . $field->id . '">field settings</a>.');

            return sprintf(
                "The <b>%s</b> field should contain no more than %d characters. %s 
<br /> <b>Source content:</b> %s 
<br /> <b>Target content:</b> %s",
                $field->name,
                $fieldSettings['charLimit'],
                $editUrl,
                $this->sourceContent,
                $this->targetContent
            );
        }

        return $reason;
    }
}
