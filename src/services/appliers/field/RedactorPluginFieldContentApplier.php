<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use craft\redactor\Field as RedactorPluginField;

class RedactorPluginFieldContentApplier extends AbstractContentApplier implements ApplierInterface
{
    public function apply(ApplyContentCommand $command): ApplyContentResult
    {
        $fieldKey = $this->getFieldKey($command->getField());
        $content = $command->getContent();

        if (!isset($content[$fieldKey])) {
            return ApplyContentResult::fail();
        }

        $command
            ->getElement()
            ->setFieldValue(
                $command->getField()->handle,
                $content[$this->getFieldKey($command->getField())]
            );

        return ApplyContentResult::applied();
    }

    public function support(ApplyContentCommand $command): bool
    {
        return $command->getField() instanceof RedactorPluginField
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}