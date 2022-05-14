<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use craft\base\FieldInterface;

abstract class AbstractContentApplier
{
    protected function getFieldKey(FieldInterface $field): string
    {
        return $field->handle;
    }
}