<?php

namespace lilthq\craftliltplugin\services\listeners;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Elements;
use lilthq\craftliltplugin\elements\Job;
use yii\base\Event;

class RegisterElementTypesListener implements ListenerInterface
{
    public function register(): void
    {
        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            [$this, '__invoke']
        );
    }

    public function __invoke(Event $event): Event
    {
        if(!$event instanceof RegisterComponentTypesEvent) {
            return $event;
        }

        $event->types[] = Job::class;

        return $event;
    }
}