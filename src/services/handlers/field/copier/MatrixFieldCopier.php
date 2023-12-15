<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers\field\copier;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\elements\MatrixBlock;
use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class MatrixFieldCopier implements FieldCopierInterface
{
    /**
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

        $blocksQuery = $to->getFieldValue($field->handle);

        /**
         * @var MatrixBlock[] $blocks
         */
        $blocks = $blocksQuery->all();

        Craft::$app->matrix->duplicateBlocks($field, $from, $to, false, false);
        Craft::$app->matrix->saveField($field, $to);

        foreach ($blocks as $block) {
            if ($block instanceof MatrixBlock) {
                Craft::$app->getElements()->deleteElement($block, true);
            }
        }

        return true;
    }
}
