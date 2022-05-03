<?php
/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\MatrixBlock;
use craft\errors\InvalidFieldException;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\redactor\Field as RedactorPluginField;
use craft\redactor\FieldData as RedactorPluginFieldData;

class ElementTranslatableContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ElementInterface $element): array
    {
        $content = [];

        $elementKey = get_class($element)
            . '.' . $element->getId()
            . (!empty($element->handle) ? '.' . $element->handle : '');

        if (!empty($element->title) && $element->getIsTitleTranslatable()) {
            $content['title'] = $element->title;
        }

        if (!empty($element->slug)) {
            $content['slug'] = $element->slug;
        }

        $fieldLayout = $element->getFieldLayout();

        if ($fieldLayout === null) {
            //TODO: log issue
        }

        $fields = $fieldLayout ? $fieldLayout->getFields() : [];

        foreach ($fields as $field) {
            $fieldData = Craft::$app->fields->getFieldById((int)$field->id);

            if($fieldData === null) {
                //TODO: log issue
                continue;
            }

            $fieldDataKey = get_class($fieldData) . '.' . $fieldData->id . '.' . $fieldData->handle;

            $content[$fieldDataKey]['content'] = null;

            if ($fieldData instanceof PlainText && $fieldData->getIsTranslatable($element)) {
                $content[$fieldDataKey]['content'] = $element->getFieldValue($fieldData->handle);

                continue;
            }

            if ($fieldData instanceof RedactorPluginField && $fieldData->getIsTranslatable($element)) {
                /**
                 * @var RedactorPluginFieldData $redactorFieldData
                 */
                $redactorFieldData = $element->getFieldValue($fieldData->handle);
                $content[$fieldDataKey]['content'] = $redactorFieldData->getRawContent();
            }

            if ($fieldData instanceof Matrix) {
                /**
                 * @var MatrixBlockQuery $fieldValue
                 */
                $matrixBlockQuery = $element->getFieldValue($fieldData->handle);

                /**
                 * @var MatrixBlock[] $blockElements
                 */
                $blockElements = $matrixBlockQuery->all();
                $blocksContent = [];
                foreach ($blockElements as $blockElement) {
                    $blocksContent[] = $this->provide($blockElement);
                }
                $content[$fieldDataKey]['content'] = ['blocks' => $blocksContent];
            }

            if ($content[$fieldDataKey]['content'] === null) {
                unset($content[$fieldDataKey]);
            }
        }

        return [
            $elementKey => $content
        ];
    }
}