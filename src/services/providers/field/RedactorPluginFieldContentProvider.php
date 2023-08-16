<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\services\providers\command\ProvideContentCommand;

class RedactorPluginFieldContentProvider extends AbstractContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ProvideContentCommand $provideContentCommand): ?string
    {
        $element = $provideContentCommand->getElement();
        $field = $provideContentCommand->getField();

        $fieldValue = $element->getFieldValue($field->handle);

        if (!$fieldValue) {
            return null;
        }

        return $fieldValue->getRawContent();
    }

    public function support(ProvideContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::CRAFT_REDACTOR_FIELD
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
