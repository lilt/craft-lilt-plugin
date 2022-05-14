<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\MatrixBlock;
use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\services\providers\ElementTranslatableContentProvider;

class AbstractContentApplier
{
    protected function getFieldKey(FieldInterface $field): string
    {
        return $field->handle;
    }
}