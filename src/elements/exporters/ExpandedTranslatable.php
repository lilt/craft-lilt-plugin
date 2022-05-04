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
        $fields = Craft::$app->getFields()->getAllFields();

        foreach ($fields as $field) {
            if ($field instanceof EagerLoadingFieldInterface) {
                $eagerLoadableFields[] = $field->handle;
            }
        }

        $data = [];

        /** @var ElementQuery $query */
        $query->with($eagerLoadableFields);

        foreach (Db::each($query) as $element) {
            /** @var ElementInterface $element */

            //TODO: apply only translatable map
            $translatableFieldsMap = $this->getTranslatableFieldsMap($element);

            $elementArr = $element->toArray(
                $this->getTranslatableDefaultFields($element)
            );

            $fieldLayout = $element->getFieldLayout();
            if ($fieldLayout !== null) {
                foreach ($fieldLayout->getFields() as $field) {
                    $value = $element->getFieldValue($field->handle);
                    $elementArr[$field->handle] = $field->serializeValue($value, $element);
                }
            }
            $data[] = $elementArr;
        }

        return $data;
    }

    public function getTranslatableFieldsMap(ElementInterface $element): array
    {
        $translatableFieldsMap = [];

        $fieldLayout = $element->getFieldLayout();

        if($fieldLayout === null)
        {
            //TODO: exception maybe?
            return [];
        }

        $fields = $fieldLayout->getFields();

        foreach ($fields as $field) {

            $fieldValue = $element->getFieldValue($field->handle);

            if($fieldValue instanceof ElementQueryInterface)
            {
                /** Nested field  */
                $nestedFieldElements = $fieldValue->all();
                foreach ($nestedFieldElements as $nestedFieldElement) {
                    $translatableFieldsMap[$field->handle][$nestedFieldElement->id] = $this->getTranslatableFieldsMap($nestedFieldElement);
                }

                continue;
            }

            $translatableFieldsMap[$field->handle] = $field->getIsTranslatable($element);
        }

        return $translatableFieldsMap;
    }

    public function getTranslatableDefaultFields(ElementInterface $element): array
    {
        $fields = [];
        if ($element instanceof Entry) {
            if ($element->getIsTitleTranslatable()) {
                $fields['title'] = $element->title;
            }
            $fields['slug'] = $element->slug;
        }

        return $fields;
    }
}
