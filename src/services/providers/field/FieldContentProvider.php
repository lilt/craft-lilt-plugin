<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\base\ElementInterface;
use craft\base\FieldInterface;

class FieldContentProvider
{
    public $providersMap;

    public function provide(ElementInterface $element, FieldInterface $field): ?array
    {
        $fieldClass = get_class($field);

        if(!isset($this->providersMap[$fieldClass])) {
            return null;
        }

        return $this->providersMap[$fieldClass]->provide($element, $field);
    }
}