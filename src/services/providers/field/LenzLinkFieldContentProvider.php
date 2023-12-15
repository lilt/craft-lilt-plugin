<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\errors\InvalidFieldException;
use fruitstudios\linkit\fields\LinkitField;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\models\input\InputLink;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\services\providers\command\ProvideContentCommand;

class LenzLinkFieldContentProvider extends AbstractContentProvider
{
    /**
     * @throws InvalidFieldException
     */
    public function provide(ProvideContentCommand $provideContentCommand): ?string
    {
        /** @var LinkField $field */
        $field = $provideContentCommand->getField();

        /**
         * @var InputLink $fieldValue
         */
        $fieldValue = $provideContentCommand->getElement()->getFieldValue(
            $field->handle
        );

        return $fieldValue->getCustomText();
    }

    public function support(ProvideContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::LENZ_LINKFIELD
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
