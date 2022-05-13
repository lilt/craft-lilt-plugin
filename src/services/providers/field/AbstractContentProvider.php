<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\MatrixBlock;
use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\services\providers\ElementTranslatableContentProvider;

class AbstractContentProvider
{
    protected function getFieldKey(FieldInterface $field): string
    {
        return $field->handle;
    }
}