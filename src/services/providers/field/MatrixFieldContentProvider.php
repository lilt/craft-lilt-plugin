<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\MatrixBlock;
use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\services\providers\ElementTranslatableContentProvider;

class MatrixFieldContentProvider extends AbstractContentProvider
{
    /**
     * @var ElementTranslatableContentProvider
     */
    public $elementTranslatableContentProvider;

    /**
     * @throws InvalidFieldException
     */
    public function provide(ElementInterface $element, FieldInterface $field): array
    {
        $content = [];

        $matrixBlockQuery = $element->getFieldValue($field->handle);
        assert($matrixBlockQuery instanceof MatrixBlockQuery);

        /**
         * @var MatrixBlock[] $blockElements
         */
        $blockElements = $matrixBlockQuery->all();

        $blocksContent = [];

        foreach ($blockElements as $blockElement) {
            $blockId = $blockElement->getId();
            $blocksContent[$blockId]['fields'] = $this->elementTranslatableContentProvider->provide($blockElement)[$blockId];
        }

        $content[$this->getFieldKey($field)] = $blocksContent;

        return $blocksContent;
    }
}