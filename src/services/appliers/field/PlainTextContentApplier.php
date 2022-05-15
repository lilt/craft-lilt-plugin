<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class PlainTextContentApplier extends AbstractContentApplier implements ApplierInterface
{
    public function apply(ApplyContentCommand $command): ApplyContentResult
    {
        $fieldKey = $this->getFieldKey($command->getField());
        $content = $command->getContent();

        if (!isset($content[$fieldKey])) {
            return ApplyContentResult::fail();
        }

        $command->getElement()->setFieldValue($command->getField()->handle, $content[$fieldKey]);

        $this->forceSave($command);

        return ApplyContentResult::applied();
    }

    public function support(ApplyContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_PLAINTEXT
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}