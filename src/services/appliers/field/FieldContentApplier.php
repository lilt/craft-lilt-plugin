<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

class FieldContentApplier
{
    /**
     * @var ApplierInterface[]
     */
    public $appliersMap;

    /**
     * @return mixed
     */
    public function apply(ApplyContentCommand $command): ApplyContentResult
    {
        $fieldClass = get_class($command->getField());

        if(!isset($this->appliersMap[$fieldClass])) {
            return ApplyContentResult::fail();
        }

        if(!$this->appliersMap[$fieldClass]->support($command)) {
            return false;
        }

        return $this->appliersMap[$fieldClass]->apply($command);
    }
}