<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\errors\InvalidFieldException;
use fruitstudios\linkit\fields\LinkitField;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class LinkitContentProvider extends AbstractContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ProvideContentCommand $provideContentCommand): array
    {
        /** @var LinkitField $field */
        $field = $provideContentCommand->getField();

        $fieldValue = $field->serializeValue(
            $provideContentCommand->getElement()->getFieldValue($field->handle),
            $provideContentCommand->getElement()
        );

        $customLabels = [];

        foreach ($field->types as $key => $data) {
            if (empty($data['customLabel'])) {
                continue;
            }

            $customLabels[$key] = $data['customLabel'];
        }

        $content = [
            'defaultText' => $field->defaultText ?? null,
            'customLabels' => $customLabels,
        ];

        if (isset($fieldValue['type'])) {
            $content['value'] = [
                $fieldValue['type'] => [
                    'value' => $fieldValue['value'],
                    'customText' => $fieldValue['customText'],
                ]
            ];
        }

        return $content;
    }

    public function support(ProvideContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::LINKIT_FIELD
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
