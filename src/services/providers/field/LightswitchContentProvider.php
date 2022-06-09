<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class LightswitchContentProvider extends AbstractContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ProvideContentCommand $provideContentCommand): array
    {
        $field = $provideContentCommand->getField();

        return [
            'onLabel' => $field->onLabel,
            'offLabel' => $field->offLabel,
        ];
    }

    public function support(ProvideContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_LIGHTSWITCH
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
