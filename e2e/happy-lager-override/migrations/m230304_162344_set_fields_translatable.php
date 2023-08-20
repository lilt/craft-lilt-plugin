<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\base\Field;
use craft\db\Migration;
use craft\fields\Matrix;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

/**
 * m230304_162344_set_fields_translatable migration.
 */
class m230304_162344_set_fields_translatable extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $fields = Craft::$app->getFields()->getAllFields();

        foreach ($fields as $field) {
            $field->translationMethod = Field::TRANSLATION_METHOD_SITE;
            if(get_class($field) === CraftliltpluginParameters::CRAFT_FIELDS_MATRIX) {
                /**
                 * @var Matrix $field
                 */
                $field->propagationMethod = Matrix::PROPAGATION_METHOD_LANGUAGE;

                /**
                 * @var Matrix $field
                 */
                foreach ($field->getBlockTypeFields() as $matrixField) {
                    $matrixField->translationMethod = Field::TRANSLATION_METHOD_SITE;
                    Craft::$app->getFields()->saveField($matrixField);
                }
            }

            Craft::$app->getFields()->saveField($field);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m230304_162344_set_fields_translatable can't be reverted.\n";

        return true;
    }
}
