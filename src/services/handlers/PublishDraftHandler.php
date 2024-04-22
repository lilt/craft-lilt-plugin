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
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Translation;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\SettingRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\handlers\commands\PublishDraftCommand;
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
    public function __invoke(PublishDraftCommand $command): void
    {
        $draftElement = Craft::$app->elements->getElementById(
            $command->getDraftId(),
            null,
            $command->getTargetSiteId()
        );

        if (!$draftElement) {
            return;
        }

        $enableEntriesForTargetSitesRecord = SettingRecord::findOne(['name' => 'enable_entries_for_target_sites']);
        $enableEntriesForTargetSites = (bool)($enableEntriesForTargetSitesRecord->value
            ?? false);

        $element = $this->apply($draftElement);
        if ($enableEntriesForTargetSites && !$draftElement->getEnabledForSite($command->getTargetSiteId())) {
            $element->setEnabledForSite([$command->getTargetSiteId() => true]);
        }

        Craft::$app->getElements()->saveElement($element, true, false, false);
        Craft::$app->getElements()->invalidateCachesForElement($element);

        // finish publishing
        $updated = TranslationRecord::updateAll(
            ['status' => TranslationRecord::STATUS_PUBLISHED],
            ['id' => $command->getTranslationId()]
        );

        Craft::$app->getElements()->invalidateCachesForElementType(Translation::class);

        if ($updated) {
            Craftliltplugin::getInstance()->jobLogsRepository->create(
                $command->getJobId(),
                Craft::$app->getUser()->getId(),
                sprintf('Translation (id: %d) published', $command->getTranslationId())
            );
        }
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
