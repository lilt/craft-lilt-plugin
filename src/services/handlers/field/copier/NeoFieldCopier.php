<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers\field\copier;

use benf\neo\elements\Block;
use benf\neo\elements\db\BlockQuery;
use benf\neo\Field;
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
     * @param FieldInterface|Field $field
     * @return void
     * @throws InvalidFieldException
     * @throws \Throwable
     */
    private function removeBlocks(ElementInterface $to, FieldInterface $field): void
    {
        /**
         * @var BlockQuery $blocksQuery
         */
        $blocksQuery = $to->getFieldValue($field->handle);

        /**
         * @var Block[] $blocks
         */
        $blocks = $blocksQuery->all();

        foreach ($blocks as $block) {
            if (!$block instanceof Block) {
                continue;
            }

            Craft::$app->getElements()->deleteElement($block, true);
        }

        // Get the Neo plugin instance
        /** @var \benf\neo\Plugin $neoPluginInstance */
        $neoPluginInstance = call_user_func(['benf\neo\Plugin', 'getInstance']);

        // Get the Neo plugin Fields service
        /** @var \benf\neo\services\Fields $neoPluginFieldsService  */
        $neoPluginFieldsService = $neoPluginInstance->get('fields');

        //Save field value
        $neoPluginFieldsService->saveValue($field, $to);
    }
}
