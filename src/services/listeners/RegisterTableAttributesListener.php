<?php

namespace lilthq\craftliltplugin\services\listeners;

use Craft;
use craft\base\Element;
use craft\events\RegisterElementTableAttributesEvent;
use yii\base\Event;

class RegisterTableAttributesListener implements ListenerInterface
{
    public function register(): void
    {
        Event::on(
            Element::class,
            Element::EVENT_REGISTER_TABLE_ATTRIBUTES,
            [$this, '__invoke']
        );
    }

    public function __invoke(Event $event): Event
    {
        if (!$event instanceof RegisterElementTableAttributesEvent) {
            return $event;
        }

        $params = Craft::$app->getRequest()->getBodyParams();

        if (
            !empty($params['elementType'])
            && isset($event->tableAttributes['drafts']['label'])
            && $params['elementType'] === 'lilthq\craftliltplugin\elements\TranslateEntry'
        ) {
            $event->tableAttributes['drafts']['label'] = 'Version';
        }

        return $event;
    }
}
