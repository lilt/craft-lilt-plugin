<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\services\providers\command\ProvideContentCommand;

interface ContentProviderInterface
{
    /**
     * @param ProvideContentCommand $provideContentCommand
     * @return mixed
     *
     * @throws InvalidFieldException
     */
    public function provide(ProvideContentCommand $provideContentCommand);
    public function support(ProvideContentCommand $command): bool;
}
