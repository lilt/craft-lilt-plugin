<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\listeners;

use yii\base\Event;

interface ListenerInterface
{
    public function register(): void;
    public function __invoke(Event $event): Event;
}
