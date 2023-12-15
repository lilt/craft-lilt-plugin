<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers\field\copier;

use Craft;
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

        // Get the Super Table plugin instance
        $superTablePluginInstance = call_user_func(['verbb\supertable\SuperTable', 'getInstance']);

        // Get the Super Table plugin service
        /** @var \verbb\supertable\services\SuperTableService $superTablePluginService */
        $superTablePluginService = $superTablePluginInstance->getService();

        // Clear current Supertable field value
        $supertableField = $to->getFieldValue($field->handle);
        foreach ($supertableField as $block) {
            Craft::$app->getElements()->deleteElement($block);
        }
        Craft::$app->getElements()->saveElement($to);

        // Duplicate the blocks for the field
        $superTablePluginService->duplicateBlocks($field, $from, $to);

        return true;
    }
}
