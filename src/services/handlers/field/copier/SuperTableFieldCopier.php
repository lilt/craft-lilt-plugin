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

        $this->removeBlocks($to, $field);

        $serializedValue = $field->serializeValue($from->getFieldValue($field->handle), $from);

        $prepared = [];
        $i = 1;
        foreach ($serializedValue as $item) {
            $prepared[sprintf('new%d', $i++)] = $item;
        }

        $to->setFieldValues([$field->handle => $prepared]);

        return true;
    }

    /**
     * @param ElementInterface $to
     * @param FieldInterface|SuperTableField $field
     * @return void
     * @throws InvalidFieldException
     * @throws \Throwable
     */
    private function removeBlocks(ElementInterface $to, FieldInterface $field): void
    {
        /**
         * @var SuperTableBlockQuery $blocksQuery
         */
        $blocksQuery = $to->getFieldValue($field->handle);

        /**
         * @var SuperTableBlockElement[] $blocks
         */
        $blocks = $blocksQuery->all();

        foreach ($blocks as $block) {
            if (!$block instanceof SuperTableBlockElement) {
                continue;
            }

            Craft::$app->getElements()->deleteElement($block, true);
        }

        // Get the Super Table plugin instance
        $superTablePluginInstance = call_user_func(['verbb\supertable\SuperTable', 'getInstance']);

        // Get the Super Table plugin service
        /** @var \verbb\supertable\services\Service $superTablePluginService */
        $superTablePluginService = $superTablePluginInstance->getService();

        // Save Super Table field
        $superTablePluginService->saveField($field, $to);
    }
}
