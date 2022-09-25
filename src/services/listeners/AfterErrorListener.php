<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\listeners;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Elements;
use lilthq\craftliltplugin\elements\Job;
use yii\base\Event;
use yii\queue\ExecEvent;
use yii\queue\Queue;

class AfterErrorListener implements ListenerInterface
{
    public function register(): void
    {
        Event::on(
            Queue::class,
            Queue::EVENT_AFTER_ERROR,
            [$this, '__invoke']
        );
    }

    public function __invoke(Event $event): Event
    {
        if (!$event instanceof ExecEvent) {
            return $event;
        }

        return $event;
    }
}
