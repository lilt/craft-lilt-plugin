<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\errors\InvalidFieldException;
use craft\fields\Table;

class TableContentProvider extends AbstractContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ElementInterface $element, FieldInterface $field): array
    {
        assert($field instanceof Table);

        $value = $element->getFieldValue($field->handle);

        if(empty($value)) {
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
}