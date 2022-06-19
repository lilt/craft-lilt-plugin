<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class TableContentApplier extends AbstractContentApplier implements ApplierInterface
{
    public function apply(ApplyContentCommand $command): ApplyContentResult
    {
        $fieldKey = $this->getFieldKey($command->getField());
        $content = $command->getContent();
        $field = $command->getField();
        $i18NRecords = [];

        if (!isset($content[$fieldKey])) {
            //TODO: check this case
            return ApplyContentResult::fail();
        }

        if (!isset($content[$field->handle]['content'])) {
            //TODO: check this case
            return ApplyContentResult::fail();
        }

        $tableSource = $content[$field->handle]['content'];
        foreach ($field->columns as $column => $columnData) {
            foreach ($tableSource as $rowId => $rows) {
                $tableSource[$rowId][$column] = $rows[$columnData['handle']];
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

        $command->getElement()->setFieldValue($field->handle, $content[$field->handle]);

        $this->forceSave($command);

        return ApplyContentResult::applied($i18NRecords, null);
    }

    public function support(ApplyContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_TABLE
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
