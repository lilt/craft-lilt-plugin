<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\base\FieldInterface;

abstract class AbstractContentProvider implements ContentProviderInterface
{
    protected function getFieldKey(FieldInterface $field): string
    {
        return $field->handle;
    }
}
