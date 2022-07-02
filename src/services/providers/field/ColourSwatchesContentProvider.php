<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use percipioglobal\colourswatches\fields\ColourSwatches;

class ColourSwatchesContentProvider extends AbstractContentProvider
{
    public function provide(ProvideContentCommand $provideContentCommand): array
    {
        /** @var ColourSwatches $field */
        $field = $provideContentCommand->getField();

        if (empty($field->options)) {
            return [];
        }

        $labels = [];
        foreach ($field->options as $option) {
            if (empty($option['label'])) {
                continue;
            }

            $key = md5(sprintf('%s%s', $option['color'], $option['label']));

            $labels[$key] = $option['label'];
        }

        return [
            'labels' => $labels
        ];
    }

    public function support(ProvideContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::COLOUR_SWATCHES_FIELD
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
