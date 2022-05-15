<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\elements\MatrixBlock;
use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\services\providers\ElementTranslatableContentProvider;

class MatrixFieldContentProvider extends AbstractContentProvider
{
    /**
     * @var ElementTranslatableContentProvider
     */
    private $elementTranslatableContentProvider;

    public function __construct(ElementTranslatableContentProvider $elementTranslatableContentProvider)
    {
        $this->elementTranslatableContentProvider = $elementTranslatableContentProvider;
    }

    /**
     * @throws InvalidFieldException
     */
    public function provide(ProvideContentCommand $provideContentCommand): array
    {
        $element = $provideContentCommand->getElement();
        $field = $provideContentCommand->getField();

        $matrixBlockQuery = $element->getFieldValue($field->handle);

        /**
         * @var MatrixBlock[] $blockElements
         */
        $blockElements = $matrixBlockQuery->all();

        $blocksContent = [];

        foreach ($blockElements as $blockElement) {
            $blockId = $blockElement->getId();
            $blocksContent[$blockId]['fields'] = $this->elementTranslatableContentProvider->provide($blockElement)[$blockId];
        }

        return $blocksContent;
    }

    public function support(ProvideContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_MATRIX;
    }
}
