<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use craft\errors\InvalidFieldException;
use fruitstudios\linkit\fields\LinkitField;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\models\input\InputLink;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class LenzLinkFieldContentApplier extends AbstractContentApplier implements ApplierInterface
{
    /**
     * @throws InvalidFieldException
     */
    public function apply(ApplyContentCommand $command): ApplyContentResult
    {
        /** @var LinkField $field */
        $field = $command->getField();
        $fieldKey = $this->getFieldKey($command->getField());
        $content = $command->getContent();

        if (!isset($content[$fieldKey])) {
            return ApplyContentResult::fail();
        }

        /**
         * @var InputLink $fieldValue
         */
        $fieldValue = $command->getElement()->getFieldValue(
            $field->handle
        );
        $fieldValue->customText = $content[$fieldKey];

        $command->getElement()->setFieldValue($field->handle, $fieldValue);

        return ApplyContentResult::applied();
    }

    public function support(ApplyContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::LENZ_LINKFIELD
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
