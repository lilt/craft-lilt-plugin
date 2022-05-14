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
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\helpers\Db;

/**
 * @deprecated
 */
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

            $elementArr = $this->getTranslatableDefaultFields($element);

            $fieldLayout = $element->getFieldLayout();
            if ($fieldLayout !== null) {
                foreach ($fieldLayout->getFields() as $field) {
                    if (isset($translatableFieldsMap[$field->handle]) && !is_array(
                            $translatableFieldsMap[$field->handle]
                        ) && !$translatableFieldsMap[$field->handle]) {
                        //field not translatable
                        continue;
                    }

                    $value = $element->getFieldValue($field->handle);
                    $serializedValue = $field->serializeValue($value, $element);
                    if(empty($serializedValue)) {
                        continue;
                    }
                    $elementArr[$field->handle] = $serializedValue;
                }
            }

            $data[$element->id] = $elementArr;
        }

        return $this->clearFields($data);
    }

    private function clearFields(array $array): array
    {
        foreach ($array as $key => $item) {
            if (is_array($item)) {
                $array[$key] = $this->clearFields($item);
                continue;
            }

            if (array_key_exists('collapsed', $array)) {
                unset($array[$key]);
            }

            if (array_key_exists('enabled', $array)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    public function getTranslatableFieldsMap(ElementInterface $element): array
    {
        $translatableFieldsMap = [];

        $fieldLayout = $element->getFieldLayout();

        if ($fieldLayout === null) {
            //TODO: exception maybe?
            return [];
        }

        $fields = $fieldLayout->getFields();

        foreach ($fields as $field) {
            $fieldValue = $element->getFieldValue($field->handle);

            if ($fieldValue instanceof AssetQuery) {
                $translatableFieldsMap[$field->handle] = false;

                continue;
            }

            if ($fieldValue instanceof ElementQueryInterface) {
                /** Nested field  */
                $nestedFieldElements = $fieldValue->all();
                foreach ($nestedFieldElements as $nestedFieldElement) {
                    $translatableFieldsMap[$field->handle][$nestedFieldElement->id] = $this->getTranslatableFieldsMap(
                        $nestedFieldElement
                    );
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
