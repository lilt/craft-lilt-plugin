<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\base\ElementInterface;
use craft\services\Drafts as DraftRepository;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
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
            return;
        }

        $enableEntriesForTargetSitesRecord = SettingRecord::findOne(['name' => 'enable_entries_for_target_sites']);
        $enableEntriesForTargetSites = (bool)($enableEntriesForTargetSitesRecord->value
            ?? false);

        if (method_exists($draftElement, 'setIsFresh')) {
            $draftElement->setIsFresh();
            Craft::$app->getElements()->saveElement($draftElement);
        }

        //TODO: ENG-6776
        //It is a bit unclear why canonical changes doesn't appear on the draft after we publish another draft
        $this->updateDraftToCanonicalChanges($targetSiteId, $draftElement);

        $element = $this->draftRepository->applyDraft($draftElement);
        if ($enableEntriesForTargetSites && !$draftElement->getEnabledForSite($targetSiteId)) {
            $element->setEnabledForSite([$targetSiteId => true]);
        }

        Craft::$app->getElements()->saveElement($element);
        Craft::$app->getElements()->invalidateCachesForElement($element);
    }

    /**
     *
     * Function to copy content for languages except skipped from canonical element to draft
     *
     * @param int $skippedSiteId
     * @param ElementInterface $draftElement
     * @return void
     * @throws Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    private function updateDraftToCanonicalChanges(int $skippedSiteId, ElementInterface $draftElement): void
    {
        $availableSites = Craftliltplugin::getInstance()->languageMapper->getAvailableSites();
        foreach ($availableSites as $availableSite) {
            if ($availableSite->id === $skippedSiteId) {
                continue;
            }

            $canonicalElement = Craft::$app->elements->getElementById(
                $draftElement->getCanonicalId(),
                null,
                $availableSite->id
            );

            if ($canonicalElement === null) {
                continue;
            }

            $draftElementOtherSite = Craft::$app->elements->getElementById(
                $draftElement->id,
                null,
                $availableSite->id
            );

            if ($draftElementOtherSite === null) {
                continue;
            }

            $fieldLayout = $canonicalElement->getFieldLayout();
            $fields = $fieldLayout ? $fieldLayout->getFields() : [];

            foreach ($fields as $field) {
                if (get_class($field) === CraftliltpluginParameters::CRAFT_FIELDS_MATRIX) {
                    Craft::$app->matrix->duplicateBlocks(
                        $field,
                        $canonicalElement,
                        $draftElementOtherSite,
                        false,
                        false
                    );
                    Craft::$app->matrix->saveField($field, $draftElementOtherSite);

                    continue;
                }

                $field->copyValue($canonicalElement, $draftElementOtherSite);
            }

            Craft::$app->getElements()->saveElement($draftElementOtherSite);
        }
    }
}
