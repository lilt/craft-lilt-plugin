<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use benf\neo\elements\Block;
use benf\neo\elements\db\BlockQuery;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\Craftliltplugin;

class NeoFieldContentProvider extends AbstractContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ElementInterface $element, FieldInterface $field): array
    {
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
}