<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers\field;

interface ContentProviderInterface
{
    public function provide(ProvideContentCommand $provideContentCommand);
    public function support(ProvideContentCommand $command): bool;
}