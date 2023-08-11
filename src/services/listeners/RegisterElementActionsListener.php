<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\listeners;

use Craft;
use craft\base\Element;
use craft\events\RegisterCpAlertsEvent;
use craft\events\RegisterElementActionsEvent;
use craft\helpers\Cp;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use yii\base\Event;

class RegisterElementActionsListener implements ListenerInterface
{
    public function register(): void
    {
        Event::on(
            Job::class,
            Element::EVENT_REGISTER_ACTIONS,
            [$this, '__invoke']
        );
    }

    public function __invoke(Event $event): Event
    {
        if (!$event instanceof RegisterElementActionsEvent) {
            return $event;
        }

        foreach ($event->actions as $key => $action) {
            if (!is_array($action)) {
                continue;
            }

            if ($action['type'] === 'craft\elements\actions\Edit') {
                unset($event->actions[$key]);
            }
        }

        return $event;
    }
}
