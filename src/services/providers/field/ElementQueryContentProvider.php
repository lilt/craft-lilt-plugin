<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\base\Element;
use craft\elements\db\ElementQuery;
use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\services\providers\command\ProvideContentCommand;

class ElementQueryContentProvider extends AbstractContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ProvideContentCommand $provideContentCommand): array
    {
        $element = $provideContentCommand->getElement();
        $field = $provideContentCommand->getField();

        $content = [];

        /**
         * @var ElementQuery
         */
        $matrixBlockQuery = $element->getFieldValue($field->handle);

        /**
         * @var Element[] $blockElements
         */
        $blockElements = $matrixBlockQuery->all();

        foreach ($blockElements as $blockElement) {
            $blockId = $blockElement->getId();

            $blockFields = Craftliltplugin::getInstance()
                ->elementTranslatableContentProvider
                ->provide($blockElement)[$blockId];

            if (!empty($blockFields)) {
                $content[$blockId]['fields'] = $blockFields;
            }
        }

        return $content;
    }

    public function support(ProvideContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_MATRIX
            || get_class($command->getField()) === CraftliltpluginParameters::BENF_NEO_FIELD
            || get_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_SUPER_TABLE;
    }
}
