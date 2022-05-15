<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use craft\elements\db\MatrixBlockQuery;
use craft\elements\MatrixBlock;
use craft\fields\Matrix;
use lilthq\craftliltplugin\Craftliltplugin;

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

                    if (!isset(
                        $content[$field->handle][$blockId]['fields'][$blockField->handle]
                    )) {
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

        return ApplyContentResult::applied(
            array_merge(...$i18NRecords)
        );
    }

    public function support(ApplyContentCommand $command): bool
    {
        return $command->getField() instanceof Matrix;
    }

    private function merge(array $original, array $new): array
    {
        foreach ($new as $key => $newItem) {
            if (!array_key_exists(
                $key,
                $original
            )) { //TODO: looks like content can be empty? Is it change? || empty($original[$key])) {
                //TODO: log issue? How we can't have key in original?
                $original[$key] = $newItem;
                continue;
            }

            if (is_array($newItem)) {
                if (isset($original[$key]) && !is_array($original[$key])) {
                    continue;
                }

                $original[$key] = $this->merge($original[$key] ?? [], $newItem);

                continue;
            }

            $original[$key] = $newItem;
        }

        return $original;
        #return array_merge_recursive($original, $new);
    }
}