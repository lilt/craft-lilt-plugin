<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use lilthq\craftliltplugin\services\handlers\commands\PublishDraftCommand;

interface PublishDraftHandlerInterface
{
    public function __invoke(PublishDraftCommand $command): void;
}
