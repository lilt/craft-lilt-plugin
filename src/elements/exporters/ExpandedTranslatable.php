<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\elements\exporters;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\ElementExporter;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\helpers\Db;

class ExpandedTranslatable extends ElementExporter
{
    /**
     * @inheritdoc
     */
    public function export(ElementQueryInterface $query): array
    {
        $eagerLoadableFields = [];
        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            if ($field instanceof EagerLoadingFieldInterface) {
                $eagerLoadableFields[] = $field->handle;
            }
        }

        $data = [];

        /** @var ElementQuery $query */
        $query->with($eagerLoadableFields);

        foreach (Db::each($query) as $element) {
            /** @var ElementInterface $element */

            $elementArr = $element->toArray(
                $this->getTranslatableDefaultFields($element)
            );

            $fieldLayout = $element->getFieldLayout();
            if ($fieldLayout !== null) {
                foreach ($fieldLayout->getFields() as $field) {
                    $isTranslatable = $field->getIsTranslatable($element);

                    if ($isTranslatable) {
                        $value = $element->getFieldValue($field->handle);
                        $elementArr[$field->handle] = $field->serializeValue($value, $element);
                    }
                }
            }
            $data[] = $elementArr;
        }

        return $data;
    }

    public function getTranslatableDefaultFields(ElementInterface $element): array
    {
        $fields = [];
        if ($element instanceof Entry) {
            if ($element->getIsTitleTranslatable()) {
                $fields[] = 'title';
            }
            $fields[] = 'slug';
        }

        return $fields;
    }
}
