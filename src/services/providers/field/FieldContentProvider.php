<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\services\providers\command\ProvideContentCommand;

class FieldContentProvider
{
    /**
     * @var ContentProviderInterface[]
     */
    public $providersMap;

    /**
     * @var string[]
     */
    public $fieldsTranslatableMap;

    /**
     * @return mixed
     */
    public function provide(ProvideContentCommand $provideContentCommand)
    {
        $fieldClass = get_class($provideContentCommand->getField());

        if (!isset($this->providersMap[$fieldClass]) || empty($this->providersMap[$fieldClass])) {
            return null;
        }

        if (!$this->providersMap[$fieldClass]->support($provideContentCommand)) {
            return null;
        }

        try {
            return $this->providersMap[$fieldClass]->provide($provideContentCommand);
        } catch (InvalidFieldException $ex) {
            \Craft::error(
                sprintf(
                    "Can't get field value %s for element %s: %s",
                    $ex->getMessage(),
                    $provideContentCommand->getField()->handle,
                    $provideContentCommand->getElement()->getId()
                )
            );

            return null;
        }
    }
}
