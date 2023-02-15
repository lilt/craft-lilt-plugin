<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\services\Drafts as DraftRepository;
use lilthq\craftliltplugin\records\SettingRecord;
use Throwable;

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
            //TODO: published already or what? Why we are here?
            return;
        }

        $enableEntriesForTargetSitesRecord = SettingRecord::findOne(['name' => 'enable_entries_for_target_sites']);
        $enableEntriesForTargetSites = (bool) ($enableEntriesForTargetSitesRecord->value
            ?? false);

        if (method_exists($draftElement, 'setIsFresh')) {
            $draftElement->setIsFresh();
        }

        Craft::$app->getElements()->saveElement($draftElement);

        $element = $this->draftRepository->applyDraft($draftElement);
        if ($enableEntriesForTargetSites && !$draftElement->getEnabledForSite($targetSiteId)) {
            $element->setEnabledForSite([$targetSiteId => true]);
        }

        Craft::$app->getElements()->saveElement($element);
        Craft::$app->getElements()->invalidateCachesForElement($element);
    }
}
