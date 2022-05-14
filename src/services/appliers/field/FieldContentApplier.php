<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use craft\base\ElementInterface;
use craft\base\FieldInterface;

class FieldContentApplier
{
    public $appliersMap;

    /**
     * @return mixed
     */
    public function apply(ElementInterface $element, FieldInterface $field)
    {
        $fieldClass = get_class($field);

        if(!isset($this->appliersMap[$fieldClass])) {
            return null;
        }

        return $this->appliersMap[$fieldClass]->provide($element, $field);
    }
}