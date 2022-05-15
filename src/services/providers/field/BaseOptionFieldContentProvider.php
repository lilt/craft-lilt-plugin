<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\errors\InvalidFieldException;
use craft\fields\BaseOptionsField;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\SingleOptionFieldData;

class BaseOptionFieldContentProvider extends AbstractContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ElementInterface $element, FieldInterface $field): ?array
    {
        assert($field instanceof BaseOptionsField);

        /**
         * @var SingleOptionFieldData|MultiOptionsFieldData $value
         */
        $value = $element->getFieldValue($field->handle);

        $options = $value->getOptions();
        $content = [];

        foreach ($options as $option) {
            $content[$option->value] = $option->label;
        }

        return [
            'class' => get_class($field),
            'fieldId' => $field->id,
            'content' => $content,
        ];
    }
}