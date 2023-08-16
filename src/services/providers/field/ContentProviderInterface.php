<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

use lilthq\craftliltplugin\services\providers\command\ProvideContentCommand;

interface ContentProviderInterface
{
    public function provide(ProvideContentCommand $provideContentCommand);
    public function support(ProvideContentCommand $command): bool;
}
