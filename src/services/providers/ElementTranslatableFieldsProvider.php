<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\errors\InvalidFieldException;

class ElementTranslatableFieldsProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ElementInterface $element): array
    {
        $translatableFields = [];

        $fieldLayout = $element->getFieldLayout();

        if ($fieldLayout === null) {
            //TODO: log issue
        }

        $fields = $fieldLayout ? $fieldLayout->getCustomFields() : [];

        foreach ($fields as $field) {
            $fieldData = Craft::$app->fields->getFieldById((int)$field->id);

            if ($fieldData === null) {
                continue;
            }

            $isTranslatable = $fieldData->getIsTranslatable($element);

            if ($isTranslatable) {
                $translatableFields[$fieldData->handle] = true;
            }

            $fieldValue = $element->getFieldValue($fieldData->handle);

            if ($fieldValue instanceof ElementQuery) {
                $fieldElements = $fieldValue->all();

                foreach ($fieldElements as $fieldElement) {
                    $translatableFields[$fieldData->handle][$fieldElement->getId()] = $this->provide($fieldElement);
                }
            }
        }

        return $translatableFields;
    }
}
