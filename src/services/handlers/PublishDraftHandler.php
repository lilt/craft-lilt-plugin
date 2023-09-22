<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\base\ElementInterface;
use craft\errors\InvalidElementException;
use craft\services\Drafts as DraftRepository;
use lilthq\craftliltplugin\records\SettingRecord;
use Throwable;
use yii\base\Exception;

class PublishDraftHandler
{
    /**
     * @var DraftRepository
     */
    public $draftRepository;

    /**
     * @throws Throwable
     */
    public function __invoke(int $draftId, int $targetSiteId): void
    {
        $draftElement = Craft::$app->elements->getElementById(
            $draftId,
            null,
            $targetSiteId
        );

        if (!$draftElement) {
            return;
        }

        $enableEntriesForTargetSitesRecord = SettingRecord::findOne(['name' => 'enable_entries_for_target_sites']);
        $enableEntriesForTargetSites = (bool)($enableEntriesForTargetSitesRecord->value
            ?? false);

        $element = $this->apply($draftElement);
        if ($enableEntriesForTargetSites && !$draftElement->getEnabledForSite($targetSiteId)) {
            $element->setEnabledForSite([$targetSiteId => true]);
        }

        Craft::$app->getElements()->saveElement($element, true, false, false);
        Craft::$app->getElements()->invalidateCachesForElement($element);
    }

    // copied from \craft\controllers\EntryRevisionsController::actionPublishDraft
    private function apply(ElementInterface $draft): ElementInterface
    {
        if ($draft->getIsUnpublishedDraft()) {
            /** @since setIsFresh in craft only since 3.7.14 */
            if (method_exists($draft, 'setIsFresh')) {
                $draft->setIsFresh();
            }

            $draft->propagateAll = true;
        }

        if (!Craft::$app->getElements()->saveElement($draft)) {
            throw new InvalidElementException($draft);
        }

        $isDerivative = $draft->getIsDerivative();
        if ($isDerivative) {
            $lockKey = "entry:$draft->canonicalId";
            $mutex = Craft::$app->getMutex();
            if (!$mutex->acquire($lockKey, 15)) {
                throw new Exception('Could not acquire a lock to save the entry.');
            }
        }

        try {
            $newEntry = $this->draftRepository->applyDraft($draft);
        } finally {
            if ($isDerivative) {
                $mutex->release($lockKey);
            }
        }

        return $newEntry;
    }
}
