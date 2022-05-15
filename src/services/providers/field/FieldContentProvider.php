<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

class FieldContentProvider
{
    /**
     * @var ContentProviderInterface[]
     */
    public $providersMap;

    /**
     * @return mixed
     */
    public function provide(ProvideContentCommand $provideContentCommand)
    {
        $fieldClass = get_class($provideContentCommand->getField());

        if (!isset($this->providersMap[$fieldClass])) {
            return null;
        }

        if (!$this->providersMap[$fieldClass]->support($provideContentCommand)) {
            return null;
        }

        return $this->providersMap[$fieldClass]->provide($provideContentCommand);
    }
}
