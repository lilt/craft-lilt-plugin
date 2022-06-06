<?php

namespace lilthq\craftliltplugin\services\listeners;

use Craft;
use craft\base\Element;
use craft\events\RegisterElementDefaultTableAttributesEvent;
use yii\base\Event;

class RegisterDefaultTableAttributesListener implements ListenerInterface
{
    public function register(): void
    {
        Event::on(
            Element::class,
            Element::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES,
            [$this, '__invoke']
        );
    }

    public function __invoke(Event $event): Event
    {
        if (!$event instanceof RegisterElementDefaultTableAttributesEvent) {
            return $event;
        }

        $params = Craft::$app->getRequest()->getBodyParams();

        if ($params['elementType'] === 'lilthq\craftliltplugin\elements\TranslateEntry') {
            $expiryDateKey = array_search('expiryDate', $event->tableAttributes, true);

            if ($expiryDateKey !== false) {
                unset($event->tableAttributes[$expiryDateKey]);
            }

            $event->tableAttributes = array_merge(['drafts'], $event->tableAttributes);
        }

        return $event;
    }
}
