<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers\field\copier;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

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
