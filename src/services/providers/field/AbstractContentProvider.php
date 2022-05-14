<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\base\ElementInterface;
use craft\base\FieldInterface;

abstract class AbstractContentProvider
{
    abstract public function provide(ElementInterface $element, FieldInterface $field);

    protected function getFieldKey(FieldInterface $field): string
    {
        return $field->handle;
    }
}