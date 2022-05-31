<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\listeners;

use craft\events\DraftEvent;
use craft\services\Drafts;
use yii\base\Event;

class AfterDraftAppliedListener implements ListenerInterface
{
    public function register(): void
    {
        Event::on(
            Drafts::class,
            Drafts::EVENT_AFTER_APPLY_DRAFT,
            [$this, '__invoke']
        );
    }

    public function __invoke(Event $event): Event
    {
        if (!$event instanceof DraftEvent) {
            return $event;
        }

        //TODO:

        return $event;
    }
}
