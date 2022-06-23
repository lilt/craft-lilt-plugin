<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\listeners;

use craft\events\DraftEvent;
use craft\services\Drafts;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\records\TranslationRecord;
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

        $draftId = (int)$event->draft->id;

        $translationRecord = Craftliltplugin::getInstance()
            ->translationRepository
            ->findRecordByTranslatedDraftId(
                $draftId
            );

        if ($translationRecord === null) {
            return $event;
        }

        $translationRecord->status = TranslationRecord::STATUS_PUBLISHED;
        $translationRecord->save();

        Craftliltplugin::getInstance()->refreshJobStatusHandler->__invoke(
            $translationRecord->jobId
        );

        return $event;
    }
}
