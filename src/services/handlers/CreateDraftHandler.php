<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\db\Table;
use craft\db\Table as DbTable;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidFieldException;
use craft\helpers\Db;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\datetime\DateTime;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\SettingRecord;
use lilthq\craftliltplugin\services\handlers\commands\CreateDraftCommand;
use lilthq\craftliltplugin\services\handlers\field\CopyFieldsHandler;
use Throwable;
use yii\base\Exception;

class CreateDraftHandler
{
    /**
     * @var CopyFieldsHandler
     */
    public $copyFieldsHandler;

    public function __construct(
        CopyFieldsHandler $copyFieldsHandler
    ) {
        $this->copyFieldsHandler = $copyFieldsHandler;
    }

    /**
     * @throws Exception
     * @throws Throwable
     * @throws ElementNotFoundException
     */
    public function create(
        CreateDraftCommand $command
    ): ElementInterface {
        $element = $command->getElement();
        $jobTitle = $command->getJobTitle();
        $sourceSiteId = $command->getSourceSiteId();
        $targetSiteId = $command->getTargetSiteId();

        /**
         * Element will be created from original one, we can't create draft from draft
         * @var Entry $createFrom
         */
        $createFrom = $element->getIsDraft() ? Craft::$app->elements->getElementById(
            $element->getCanonicalId()
        ) : $element;

        $creatorId = Craft::$app->user->getId();
        if ($creatorId === null) {
            $creatorId = $element->authorId;
        }

        $draft = Craft::$app->drafts->createDraft(
            $createFrom,
            $creatorId ?? 0,
            sprintf(
                '%s [%s -> %s] ' . (new DateTime())->format('H:i:s'),
                $jobTitle,
                Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId(
                    $sourceSiteId
                ),
                Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId(
                    $targetSiteId
                )
            ),
            null
        );

        $draft = Craft::$app->elements->getElementById(
            $draft->getId(),
            'craft\elements\Entry',
            $targetSiteId
        );

        $this->copyFieldsHandler->copy($element, $draft);

        $copyEntriesSlugFromSourceToTarget = SettingRecord::findOne(
            ['name' => 'copy_entries_slug_from_source_to_target']
        );
        $isCopySlugEnabled = (bool)($copyEntriesSlugFromSourceToTarget->value ?? false);

        if ($isCopySlugEnabled) {
            $draft->slug = $element->slug;
        }

        $this->markFieldsAsChanged($draft);

        $attributes = ['title'];

        if ($isCopySlugEnabled) {
            $attributes[] = 'slug';
        }
        $this->upsertChangedAttributes($draft, $attributes);

        $result = Craft::$app->elements->saveElement($draft, true, false, false);
        if (!$result) {
            Craft::error(
                sprintf(
                    "Can't save freshly createdd draft %d for site %s",
                    $draft->id,
                    Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId(
                        $targetSiteId
                    )
                )
            );
        }

        return $draft;
    }

    /**
     * @throws InvalidFieldException
     * @throws \yii\db\Exception
     */
    public function markFieldsAsChanged(ElementInterface $element): void
    {
        $fieldLayout = $element->getFieldLayout();
        $fields = $fieldLayout ? $fieldLayout->getFields() : [];

        foreach ($fields as $field) {
            if (
                get_class($field) === CraftliltpluginParameters::CRAFT_FIELDS_MATRIX
                || get_class($field) === CraftliltpluginParameters::BENF_NEO_FIELD
                || get_class($field) === CraftliltpluginParameters::CRAFT_FIELDS_SUPER_TABLE
            ) {
                /**
                 * @var ElementQuery $blockQuery
                 */
                $blockQuery = $element->getFieldValue($field->handle);

                /**
                 * @var Element[] $blockElements
                 */
                $blockElements = $blockQuery->all();

                foreach ($blockElements as $blockElement) {
                    $this->markFieldsAsChanged($blockElement);
                }

                $this->upsertChangedFields($element, $field);

                continue;
            }

            $this->upsertChangedFields($element, $field);
        }
    }


    /**
     * @throws \yii\db\Exception
     */
    private function upsertChangedFields(ElementInterface $element, FieldInterface $field): void
    {
        $userId = Craft::$app->getUser()->getId();
        $timestamp = Db::prepareDateForDb(new DateTime());

        $insert = [
            'elementId' => $element->getId(),
            'siteId' => $element->getSite()->id,
            'fieldId' => $field->id,
        ];

        $update = [
            'dateUpdated' => $timestamp,
            'propagated' => $element->propagating,
            'userId' => $userId,
        ];

        Db::upsert(
            DbTable::CHANGEDFIELDS,
            $insert,
            $update,
            [],
            false
        );
    }

    public function upsertChangedAttributes(ElementInterface $element, array $attributes): void
    {
        $userId = Craft::$app->getUser()->getId();
        $timestamp = Db::prepareDateForDb(new DateTime());

        foreach ($attributes as $attribute) {
            $insert = [
                'elementId' => $element->id,
                'siteId' => $element->siteId,
                'attribute' => $attribute,
            ];

            $update = [
                'dateUpdated' => $timestamp,
                'propagated' => $element->propagating,
                'userId' => $userId,
            ];

            Db::upsert(
                Table::CHANGEDATTRIBUTES,
                $insert,
                $update,
                [],
                false
            );
        }
    }
}
