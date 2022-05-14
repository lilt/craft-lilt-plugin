<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use craft\fields\PlainText;

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
        return ApplyContentResult::applied();
    }

    public function support(ApplyContentCommand $command): bool
    {
        return $command->getField() instanceof PlainText
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}