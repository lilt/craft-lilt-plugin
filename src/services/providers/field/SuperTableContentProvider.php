<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use verbb\supertable\elements\db\SuperTableBlockQuery;
use verbb\supertable\elements\SuperTableBlockElement;

class SuperTableContentProvider extends AbstractContentProvider
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
         * @var SuperTableBlockQuery
         */
        $fieldValue = $element->getFieldValue($field->handle);

        /**
         * @var SuperTableBlockElement[] $blockElements
         */
        $blockElements = $fieldValue->all();

        foreach ($blockElements as $blockElement) {
            $blockId = $blockElement->getId();
            $content[$blockId]['fields'] = Craftliltplugin::getInstance()
                ->elementTranslatableContentProvider
                ->provide($blockElement)[$blockId];
        }

        return $content;
    }

    public function support(ProvideContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_SUPER_TABLE;
    }
}
