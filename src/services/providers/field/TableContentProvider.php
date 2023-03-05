<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\services\providers\command\ProvideContentCommand;

class TableContentProvider extends AbstractContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ProvideContentCommand $provideContentCommand): array
    {
        $element = $provideContentCommand->getElement();
        $field = $provideContentCommand->getField();

        $value = $element->getFieldValue($field->handle);

        if (empty($value)) {
            return [];
        }

        $content = [];
        $columns = [];

        foreach ($field->columns as $key => $column) {
            foreach ($value as $rowIndex => $row) {
                $content[$rowIndex][$column['handle']] = $row[$key];
                $columns[$column['handle']] = $column['heading'];
            }
        }

        return [
            'class' => get_class($field),
            'fieldId' => $field->id,
            'columns' => $columns,
            'content' => $content,
        ];
    }

    public function support(ProvideContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_TABLE
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
