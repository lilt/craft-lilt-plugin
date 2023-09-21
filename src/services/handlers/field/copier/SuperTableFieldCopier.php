<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers\field\copier;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\MatrixBlock;
use craft\errors\InvalidFieldException;
use craft\fields\Matrix;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use verbb\supertable\elements\db\SuperTableBlockQuery;
use verbb\supertable\elements\SuperTableBlockElement;
use verbb\supertable\fields\SuperTableField;

class SuperTableFieldCopier implements FieldCopierInterface
{
    public function copy(
        FieldInterface $field,
        ElementInterface $from,
        ElementInterface $to
    ): bool {
        // Check if the field is of Super Table type and the required classes and methods are available
        if (
            get_class($field) !== CraftliltpluginParameters::CRAFT_FIELDS_SUPER_TABLE
            || !class_exists('verbb\supertable\SuperTable')
            || !method_exists('verbb\supertable\SuperTable', 'getInstance')
        ) {
            return false;
        }

        $serializedValue = $field->serializeValue($from->getFieldValue($field->handle), $from);

        $prepared = [];
        $i = 1;
        foreach ($serializedValue as $item) {
            $prepared[sprintf('new%d', $i++)] = $item;
        }

        $to->setFieldValues([$field->handle => $prepared]);

        return true;
    }
}
