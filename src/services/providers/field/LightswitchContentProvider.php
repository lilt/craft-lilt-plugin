<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\services\providers\command\ProvideContentCommand;

class LightswitchContentProvider extends AbstractContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ProvideContentCommand $provideContentCommand): array
    {
        $field = $provideContentCommand->getField();
        $content = [];

        if (!empty($field->onLabel)) {
            $content['onLabel'] = $field->onLabel;
        }

        if (!empty($field->offLabel)) {
            $content['offLabel'] = $field->offLabel;
        }

        return $content;
    }

    public function support(ProvideContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_LIGHTSWITCH
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
