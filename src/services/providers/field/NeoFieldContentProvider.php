<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use benf\neo\elements\Block;
use benf\neo\elements\db\BlockQuery;
use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class NeoFieldContentProvider extends AbstractContentProvider
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
         * @var BlockQuery
         */
        $fieldValue = $element->getFieldValue($field->handle);

        /**
         * @var Block[] $blockElements
         */
        $blockElements = $fieldValue->all();

        foreach ($blockElements as $blockElement) {
            $blockId = $blockElement->getId();
            $content[$blockId]['fields'] = Craftliltplugin::getInstance()->elementTranslatableContentProvider->provide($blockElement)[$blockId];
        }

        return $content;
    }

    public function support(ProvideContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::BENF_NEO_FIELD;
    }
}
