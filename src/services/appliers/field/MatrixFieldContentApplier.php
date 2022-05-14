<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use craft\elements\db\MatrixBlockQuery;
use craft\elements\MatrixBlock;
use craft\fields\Matrix;
use craft\fields\RadioButtons;
use craft\fields\Table;
use lilthq\craftliltplugin\records\I18NRecord;

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

            $serializedData = $field->serializeValue($matrixBlockQuery, $element);


            /**
             * @var MatrixBlock $block
             */
            foreach ($matrixBlockQuery->all() as $block) {
                foreach ($block->getFieldLayout()->getFields() as $blockField) {
                    $blockId = $block->getCanonicalId();

                    if ($blockField instanceof Table) {
                        $tableSource = $content[$field->handle][$blockId]['fields'][$blockField->handle]['content'];
                        foreach ($blockField->columns as $column => $columnData) {
                            foreach ($tableSource as $rowId => $rows) {
                                $tableSource[$rowId][$column] = $tableSource[$rowId][$columnData['handle']];
                            }
                        }
                        $content[$field->handle][$blockId]['fields'][$blockField->handle]['content'] = $tableSource;

                        if(isset($content[$field->handle][$blockId]['fields'][$blockField->handle]['columns'])) {
                            $columns = $content[$field->handle][$blockId]['fields'][$blockField->handle]['columns'];
                            foreach ($blockField->columns as $column) {
                                $translation = [
                                    'target' => $columns[$column['handle']],
                                    'source' => $column['heading'],
                                    'sourceSiteId' => $command->getSourceSiteId(),
                                    'targetSiteId' => $command->getTargetSiteId(),
                                ];

                                $translation['hash'] = md5(json_encode($translation));


                                $record = new I18NRecord();
                                $record->target = $translation['target'];
                                $record->source = $translation['source'];
                                $record->sourceSiteId = $translation['sourceSiteId'];
                                $record->targetSiteId = $translation['targetSiteId'];
                                $record->hash = $translation['hash'];

                                $i18NRecords[$record->hash] = $record;
                            }
                        }

                        $content[$field->handle][$blockId]['fields'][$blockField->handle] = $content[$field->handle][$blockId]['fields'][$blockField->handle]['content'];
                    }

                    if ($blockField instanceof RadioButtons) {
                        $options = $blockField->options;

                        if (!isset(
                            $content[$field->handle][$blockId]['fields'][$blockField->handle]
                        )) {
                            continue;
                        }

                        $optionsTranslated = $content[$field->handle][$blockId]['fields'][$blockField->handle];

                        foreach ($options as $option) {
                            $translation = [
                                'target' => $optionsTranslated[$option['value']],
                                'source' => $option['label'],
                                'sourceSiteId' => $command->getSourceSiteId(),
                                'targetSiteId' => $command->getTargetSiteId(),
                            ];

                            $translation['hash'] = md5(json_encode($translation));


                            $record = new I18NRecord();
                            $record->target = $translation['target'];
                            $record->source = $translation['source'];
                            $record->sourceSiteId = $translation['sourceSiteId'];
                            $record->targetSiteId = $translation['targetSiteId'];
                            $record->hash = $translation['hash'];

                            $i18NRecords[$record->hash] = $record;
                        }
                    }
                }
            }


            $contentWithoutIds = [$field->handle => array_values($content[$field->handle])];

            $i = 0;
            foreach ($serializedData as $key => $value) {
                $serializedData[$key] = $this->merge(
                    $serializedData[$key],
                    $contentWithoutIds[$field->handle][$i++]
                );
            }

            $element->setFieldValue($field->handle, $serializedData);
        }

        return new ApplyContentResult(false, $i18NRecords);
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