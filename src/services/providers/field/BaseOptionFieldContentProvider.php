<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\errors\InvalidFieldException;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\SingleOptionFieldData;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class BaseOptionFieldContentProvider extends AbstractContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ProvideContentCommand $provideContentCommand): array
    {
        $element = $provideContentCommand->getElement();
        $field = $provideContentCommand->getField();

        /**
         * @var SingleOptionFieldData|MultiOptionsFieldData $value
         */
        $value = $element->getFieldValue($field->handle);

        $options = $value->getOptions();
        $content = [];

        foreach ($options as $option) {
            $content[$option->value] = $option->label;
        }

        return [
            'class' => get_class($field),
            'fieldId' => $field->id,
            'content' => $content,
        ];
    }

    public function support(ProvideContentCommand $command): bool
    {
        return get_parent_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_BASEOPTIONSFIELD
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}