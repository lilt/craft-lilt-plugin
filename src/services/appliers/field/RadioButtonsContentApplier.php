<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

class RadioButtonsContentApplier extends AbstractContentApplier implements ApplierInterface
{
    public function apply(ApplyContentCommand $command): ApplyContentResult
    {
        $fieldKey = $this->getFieldKey($command->getField());
        $content = $command->getContent();

        if (!isset($content[$fieldKey])) {
            return ApplyContentResult::fail();
        }

        //TODO: implement

        return ApplyContentResult::applied();
    }

    public function support(ApplyContentCommand $command): bool
    {
        // TODO: Implement support() method.
        return false;
    }
}