<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\base\ElementInterface;
use craft\base\FieldInterface;

class ProvideContentCommand
{
    /**
     * @var ElementInterface
     */
    private $element;

    /**
     * @var FieldInterface
     */
    private $field;

    public function __construct(
        ElementInterface $element,
        FieldInterface $field
    ) {
        $this->element = $element;
        $this->field = $field;
    }

    /**
     * @return ElementInterface
     */
    public function getElement(): ElementInterface
    {
        return $this->element;
    }

    /**
     * @return FieldInterface
     */
    public function getField(): FieldInterface
    {
        return $this->field;
    }
}
