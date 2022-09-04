<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\records\Element;
use lilthq\craftliltplugin\elements\Translation;
use lilthq\craftliltplugin\records\TranslationRecord;

/**
 * m220830_181943_create_translation_elements migration.
 */
class m220830_181943_create_translation_elements extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $translations = TranslationRecord::find()->all();

        foreach ($translations as $translation) {
            $element = Element::findOne(['id' => $translation->id]);

            if ($element !== null && $element->type === 'lilthq\craftliltplugin\elements\Translation') {
                // element already exist
                continue;
            }

            $config = [
                'jobId' => $translation->jobId,
                'elementId' => $translation->elementId,
                'versionId' => $translation->versionId,
                'sourceSiteId' => $translation->sourceSiteId,
                'targetSiteId' => $translation->targetSiteId,
                'sourceContent' => $translation->sourceContent,
                'status' => $translation->status,
                'translatedDraftId' => $translation->translatedDraftId
            ];

            $translationElement = new Translation($config);
            Craft::$app->getElements()->saveElement($translationElement);

            $translation->id = $translationElement->id;
            $translation->uid = $translationElement->uid;

            $translation->save();
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $elementsTable = Table::ELEMENTS;

        $q = "DELETE FROM {$elementsTable} WHERE {$elementsTable}.type = :type";
        $this->execute($q, ['type' => Translation::class]);

        return true;
    }
}
