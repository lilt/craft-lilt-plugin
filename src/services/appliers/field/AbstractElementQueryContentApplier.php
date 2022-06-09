<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use craft\base\Element;
use craft\elements\db\ElementQuery;
use lilthq\craftliltplugin\Craftliltplugin;

abstract class AbstractElementQueryContentApplier extends AbstractContentApplier
{
    public function apply(ApplyContentCommand $command): ApplyContentResult
    {
        if (!$this->support($command)) {
            return ApplyContentResult::fail();
        }

        $field = $command->getField();
        $element = $command->getElement();
        $content = $command->getContent();

        $i18NRecords = [];

        $fieldKey = $this->getFieldKey($field);

        if (!isset($content[$fieldKey])) {
            return ApplyContentResult::fail();
        }

        /**
         * @var ElementQuery
         */
        $fieldValue = $element->getFieldValue($field->handle);

        /**
         * @var Element[] $blockElements
         */
        $blockElements = $fieldValue->all();

        foreach ($blockElements as $block) {
            foreach ($block->getFieldLayout()->getFields() as $blockField) {
                $blockId = $block->getCanonicalId();

                if (
                    !isset(
                        $content[$field->handle][$blockId]['fields'][$blockField->handle]
                    )
                ) {
                    continue;
                }

                $blockCommand = new ApplyContentCommand(
                    $block,
                    $blockField,
                    $content[$field->handle][$blockId]['fields'],
                    $command->getSourceSiteId(),
                    $command->getTargetSiteId()
                );

                $result = Craftliltplugin::getInstance()->fieldContentApplier->apply($blockCommand);

                if (!$result->isApplied()) {
                    //TODO: handle?
                }

                if ($result->isApplied()) {
                    $block->setFieldValue($field->handle, $result->getFieldValue());
                }

                $i18NRecords[] = $result->getI18nRecords();
            }
            $block->setIsFresh();
        }

        $i18NRecords = !empty($i18NRecords) ? array_merge(...$i18NRecords) : [];

        $fieldValue = $field->serializeValue($element->getFieldValue($field->handle), $element);

        $element->setFieldValue($field->handle, $fieldValue);
        $this->forceSave($command);

        return ApplyContentResult::applied($i18NRecords, $fieldValue);
    }
}
