<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\errors\InvalidFieldException;
use craft\fields\Checkboxes;
use craft\fields\Dropdown;
use craft\fields\MultiSelect;
use craft\fields\RadioButtons;
use craft\fields\Table;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\services\providers\field\ProvideContentCommand;

class ElementTranslatableContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ElementInterface $element): array
    {
        $content = [];

        $elementKey = $element->getId();

        if (!empty($element->title) && $element->getIsTitleTranslatable()) {
            $content['title'] = $element->title;
        }

        # TODO: clarify should we translate slug or not
        #if (!empty($element->slug)) {
        #    $content['slug'] = $element->slug;
        #}

        $fieldLayout = $element->getFieldLayout();

        if ($fieldLayout === null) {
            //TODO: log issue
        }

        $fields = $fieldLayout ? $fieldLayout->getFields() : [];

        foreach ($fields as $field) {
            $fieldData = Craft::$app->fields->getFieldById((int)$field->id);

            if ($fieldData === null) {
                //TODO: log issue
                continue;
            }

            $fieldDataKey = $fieldData->handle;

            $provideContentCommand = new ProvideContentCommand(
                $element,
                $fieldData
            );

            $content[$fieldDataKey] = Craftliltplugin::getInstance()->fieldContentProvider->provide(
                $provideContentCommand
            );

            if (empty($content[$fieldDataKey])) {
                unset($content[$fieldDataKey]);
            }
        }

        if ($element instanceof Entry) {
            $content = $this->clearDuplicates($element->id, $content);
        }
        return [$elementKey => $content];
    }

    private $staticContent = [];

    private function clearDuplicates(int $elementId, array $content): array
    {
        foreach ($content as $key => $item) {
            if (!is_array($item)) {
                continue;
            }

            if (
                isset($item['class'])
                && ($item['class'] === RadioButtons::class
                    || $item['class'] === Dropdown::class
                    || $item['class'] === MultiSelect::class
                    || $item['class'] === Checkboxes::class
                )
            ) {
                if (isset($this->staticContent[$elementId][$item['class']][$item['fieldId']])) {
                    unset($content[$key]);
                    continue;
                }

                $this->staticContent[$elementId][$item['class']][$item['fieldId']] = true;

                unset($item['class'], $item['fieldId']);

                $content[$key] = $item['content'];
                continue;
            }

            if (isset($item['class']) && $item['class'] === Table::class) {
                if (isset($this->staticContent[$elementId][$item['class']][$item['fieldId']])) {
                    unset($content[$key]['columns'], $content[$key]['class'], $content[$key]['fieldId']);
                    continue;
                }

                $this->staticContent[$elementId][$item['class']][$item['fieldId']] = true;

                unset($item['class'], $item['fieldId']);

                $content[$key] = $item;
                continue;
            }

            $content[$key] = $this->clearDuplicates($elementId, $item);
        }

        return $content;
    }
}
