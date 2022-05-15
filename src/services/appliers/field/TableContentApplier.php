<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use benf\neo\elements\Block;
use craft\elements\MatrixBlock;
use craft\fields\Table;

class TableContentApplier extends AbstractContentApplier implements ApplierInterface
{
    public function apply(ApplyContentCommand $command): ApplyContentResult
    {
        $fieldKey = $this->getFieldKey($command->getField());
        $content = $command->getContent();
        $field = $command->getField();
        $i18NRecords = [];

        if (!isset($content[$fieldKey])) {
            return ApplyContentResult::fail();
        }

        $serializedData = $command->getElement()->getSerializedFieldValues(
            [$command->getField()->handle]
        );

        $tableSource = $content[$field->handle]['content'];
        foreach ($field->columns as $column => $columnData) {
            foreach ($tableSource as $rowId => $rows) {
                $tableSource[$rowId][$column] = $tableSource[$rowId][$columnData['handle']];
            }
        }
        $content[$field->handle]['content'] = $tableSource;

        if (isset($content[$field->handle]['columns'])) {
            $columns = $content[$field->handle]['columns'];
            foreach ($field->columns as $column) {
                $translation = [
                    'target' => $columns[$column['handle']],
                    'source' => $column['heading'],
                    'sourceSiteId' => $command->getSourceSiteId(),
                    'targetSiteId' => $command->getTargetSiteId(),
                ];

                $translation['hash'] = md5(json_encode($translation));
                $i18NRecords[$translation['hash']] = $this->createI18NRecord($translation);
            }
        }

        $content[$field->handle] = $content[$field->handle]['content'];

        $serializedData = $this->merge(
            $serializedData[$field->handle],
            $content[$field->handle]
        );

        $command->getElement()->setFieldValue($field->handle, $serializedData);

        $this->forceSave($command);

        return ApplyContentResult::applied($i18NRecords);
    }

    public function support(ApplyContentCommand $command): bool
    {
        return $command->getField() instanceof Table
            && $command->getField()->getIsTranslatable($command->getElement());
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