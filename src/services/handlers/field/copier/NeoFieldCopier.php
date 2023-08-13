<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers\field\copier;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class NeoFieldCopier implements FieldCopierInterface
{
    /**
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws InvalidConfigException
     * @throws InvalidFieldException
     * @throws Exception
     */
    public function copy(
        FieldInterface $field,
        ElementInterface $from,
        ElementInterface $to
    ): bool {

        // Check if the field is of Neo type and the required classes and methods are available
        if (
            get_class($field) !== CraftliltpluginParameters::BENF_NEO_FIELD
            || !class_exists('benf\neo\Plugin')
            || !method_exists('benf\neo\Plugin', 'getInstance')
        ) {
            return false;
        }

        // Get the Neo plugin instance
        /** @var \benf\neo\Plugin $neoPluginInstance */
        $neoPluginInstance = call_user_func(['benf\neo\Plugin', 'getInstance']);

        // Get the Neo plugin Fields service
        /** @var \benf\neo\services\Fields $neoPluginFieldsService */
        $neoPluginFieldsService = $neoPluginInstance->get('fields');

        // Duplicate the blocks for the field
        $neoPluginFieldsService->duplicateBlocks($field, $from, $to);

        Craft::$app->getElements()->saveElement($to);

        return true;
    }
}
