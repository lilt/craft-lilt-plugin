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

        $elementType = Craft::$app->getRequest()->getParam('elementType');
        $showEntryVersions = Craft::$app->getRequest()->getParam('showEntryVersions', false);

        if (
            !empty($params['elementType'])
            && isset($event->tableAttributes['drafts']['label'])
            && $elementType === 'lilthq\craftliltplugin\elements\TranslateEntry'
            && $showEntryVersions === true
        ) {
            $event->tableAttributes['drafts']['label'] = 'Version';
        }

        return $event;
    }
}
