<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use craft\elements\db\MatrixBlockQuery;
use craft\elements\MatrixBlock;
use craft\fields\Matrix;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class MatrixFieldContentApplier extends AbstractContentApplier implements ApplierInterface
{
    public function apply(ApplyContentCommand $command): ApplyContentResult
    {
        $field = $command->getField();
        $element = $command->getElement();
        $content = $command->getContent();

        $i18NRecords = [];

        $fieldKey = $this->getFieldKey($field);

        if (!isset($content[$fieldKey])) {
            return ApplyContentResult::fail();
        }

        if ($field instanceof Matrix) {
            /**
             * @var MatrixBlockQuery $fieldValue
             */
            $matrixBlockQuery = $element->getFieldValue($field->handle);

            /**
             * @var MatrixBlock $block
             */
            foreach ($matrixBlockQuery->all() as $block) {
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

                    $i18NRecords[] = $result->getI18nRecords();
                }
            }
        }

        $i18NRecords = !empty($i18NRecords) ? array_merge(...$i18NRecords) : [];

        //TODO: check this, is it only one way to apply
        $fieldValue = $field->serializeValue($element->getFieldValue($field->handle), $element);
        $element->setFieldValue($field->handle, $fieldValue);
        $this->forceSave($command);


        return ApplyContentResult::applied(
            //$i18NRecords, $command->getElement()->getFieldValue($field->handle)
            $i18NRecords,
            $fieldValue
        );
    }

    public function support(ApplyContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_MATRIX;
    }
}
