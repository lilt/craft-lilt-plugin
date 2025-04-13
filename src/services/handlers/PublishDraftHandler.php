<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\base\ElementInterface;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidElementException;
use craft\errors\InvalidFieldException;
use craft\services\Drafts as DraftRepository;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Translation;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\SettingRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\handlers\commands\PublishDraftCommand;
use lilthq\craftliltplugin\services\handlers\field\CopyFieldsHandler;
use lilthq\craftliltplugin\services\repositories\SettingsRepository;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class PublishDraftHandler
{
    /**
     * @var DraftRepository
     */
    public $draftRepository;

    /**
     * @var CopyFieldsHandler
     */
    public $copyFieldsHandler;

    /**
     * @var SettingsRepository
     */
    public $settingsRepository;

    public function __construct(
        CopyFieldsHandler $copyFieldsHandler,
        DraftRepository $draftRepository,
        SettingsRepository $settingsRepository
    ) {
        $this->copyFieldsHandler = $copyFieldsHandler;
        $this->draftRepository = $draftRepository;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @throws Throwable
     */
    public function __invoke(PublishDraftCommand $command): void
    {
        $isCopySlugEnabled = $this->settingsRepository->getBool(
            SettingsRepository::COPY_ENTRIES_SLUG_FROM_SOURCE_TO_TARGET
        );

        $draftElement = Craft::$app->elements->getElementById(
            $command->getDraftId(),
            null,
            $command->getTargetSiteId()
        );

        if (!$draftElement) {
            return;
        }

        $this->mergeCannonical($command);
        Craftliltplugin::getInstance()->createDraftHandler->markFieldsAsChanged(
            $draftElement
        );

        $attributes = ['title'];
        if ($isCopySlugEnabled) {
            $attributes[] = 'slug';
        }
        Craftliltplugin::getInstance()->createDraftHandler->upsertChangedAttributes(
            $draftElement,
            $attributes
        );

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

    /**
     * @param PublishDraftCommand $command
     * @return void
     * @throws Exception
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws InvalidFieldException
     * @throws InvalidConfigException
     */
    private function mergeCannonical(PublishDraftCommand $command): void
    {
        $isAsync = $this->settingsRepository->getBool(
            SettingsRepository::PUBLISH_TRANSLATIONS_ASYNC
        );
        $isCopySlugEnabled = $this->settingsRepository->getBool(
            SettingsRepository::COPY_ENTRIES_SLUG_FROM_SOURCE_TO_TARGET
        );

        $translation = TranslationRecord::findOne(['translatedDraftId' => $command->getDraftId()]);
        $translations = TranslationRecord::findAll(
            [
                'jobId' => $translation->jobId,
                'status' => TranslationRecord::STATUS_PUBLISHED
            ]
        );

        foreach ($translations as $translation) {
            $draftElementLanguageToUpdate = Craft::$app->elements->getElementById(
                $command->getDraftId(),
                null,
                $translation->targetSiteId
            );
            $draftElementLanguageToUpdate->mergingCanonicalChanges = true;

            if ($isAsync) {
                $this->copyFieldsHandler->copy(
                    $draftElementLanguageToUpdate->getCanonical(),
                    $draftElementLanguageToUpdate
                );

                Craftliltplugin::getInstance()->createDraftHandler->markFieldsAsChanged(
                    $draftElementLanguageToUpdate
                );

                $attributes = ['title'];
                if ($isCopySlugEnabled) {
                    $attributes[] = 'slug';
                }
                Craftliltplugin::getInstance()->createDraftHandler->upsertChangedAttributes(
                    $draftElementLanguageToUpdate,
                    $attributes
                );

                return;
            }

            $fieldLayout = $draftElementLanguageToUpdate->getFieldLayout();
            $fields = $fieldLayout ? $fieldLayout->getFields() : [];
            foreach ($fields as $field) {
                // Check if the field is of Super Table type and the required classes and methods are available
                if (
                    get_class($field) === CraftliltpluginParameters::CRAFT_FIELDS_SUPER_TABLE
                    && method_exists('verbb\supertable\SuperTable', 'getService')
                    && method_exists('verbb\supertable\SuperTable', 'getInstance')
                ) {
                    // Get the Super Table plugin instance
                    $superTablePluginInstance = call_user_func(['verbb\supertable\SuperTable', 'getInstance']);

                    // Get the Super Table plugin service
                    /** @var \verbb\supertable\services\SuperTableService $superTablePluginService */
                    $superTablePluginService = $superTablePluginInstance->getService();

                    // Duplicate the blocks for the field
                    $superTablePluginService->duplicateBlocks(
                        $field,
                        $draftElementLanguageToUpdate->getCanonical(),
                        $draftElementLanguageToUpdate
                    );

                    continue;
                }

                if (
                    get_class($field) === CraftliltpluginParameters::BENF_NEO_FIELD
                    && class_exists('benf\neo\Plugin')
                    && method_exists('benf\neo\Plugin', 'getInstance')
                ) {
                    // Get the Neo plugin instance
                    /** @var \benf\neo\Plugin $neoPluginInstance */
                    $neoPluginInstance = call_user_func(['benf\neo\Plugin', 'getInstance']);

                    // Get the Neo plugin Fields service
                    /** @var \benf\neo\services\Fields $neoPluginFieldsService */
                    $neoPluginFieldsService = $neoPluginInstance->get('fields');

                    // Clear current neo field value
                    $neoField = $draftElementLanguageToUpdate->getFieldValue($field->handle);
                    foreach ($neoField as $block) {
                        Craft::$app->getElements()->deleteElement($block);
                    }
                    Craft::$app->getElements()->saveElement($draftElementLanguageToUpdate);

                    // Duplicate the blocks for the field
                    $neoPluginFieldsService->duplicateBlocks(
                        $field,
                        $draftElementLanguageToUpdate->getCanonical(),
                        $draftElementLanguageToUpdate
                    );

                    continue;
                }
            }

            Craft::$app->getElements()->saveElement($draftElementLanguageToUpdate);
            Craft::$app->getElements()->invalidateCachesForElement($draftElementLanguageToUpdate);
        }
    }
}
