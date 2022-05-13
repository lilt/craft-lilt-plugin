<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\errors\InvalidFieldException;
use craft\redactor\FieldData as RedactorPluginFieldData;

class RedactorPluginFieldContentProvider extends AbstractContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ElementInterface $element, FieldInterface $field): array
    {
        $redactorFieldData = $element->getFieldValue($field->handle);
        assert($redactorFieldData instanceof RedactorPluginFieldData);

        $content[$this->getFieldKey($field)] = $redactorFieldData->getRawContent();

        return $content;
    }
}