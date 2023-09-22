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

class MatrixFieldCopier implements FieldCopierInterface
{
    /**
     * @param FieldInterface|Matrix $field
     * @param ElementInterface $from
     * @param ElementInterface $to
     * @return bool
     * @throws InvalidFieldException
     * @throws \Throwable
     */
    public function copy(
        FieldInterface $field,
        ElementInterface $from,
        ElementInterface $to
    ): bool {
        // Check if the field is of Matrix type
        if (get_class($field) !== CraftliltpluginParameters::CRAFT_FIELDS_MATRIX) {
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
