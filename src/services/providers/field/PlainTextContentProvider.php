<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class PlainTextContentProvider extends AbstractContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ProvideContentCommand $provideContentCommand): string
    {
        $element = $provideContentCommand->getElement();
        $field = $provideContentCommand->getField();

        return $element->getFieldValue($field->handle);
    }

    public function support(ProvideContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_PLAINTEXT
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
