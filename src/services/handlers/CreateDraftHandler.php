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
use Throwable;
use yii\base\Exception;

class CreateDraftHandler
{
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
        $createFrom = $element ? Craft::$app->elements->getElementById(
            $element->getCanonicalId()
        ) : $element;

        $creatorId = Craft::$app->user->getId();
        if ($creatorId === null) {
            $creatorId = $createFrom->authorId;
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

        $fieldLayout = $element->getFieldLayout();
        $fields = $fieldLayout ? $fieldLayout->getFields() : [];

        foreach ($fields as $field) {
            if (get_class($field) === CraftliltpluginParameters::CRAFT_FIELDS_MATRIX) {
                $draft->setFieldValue($field->handle, $draft->getFieldValue($field->handle));

                Craft::$app->matrix->duplicateBlocks($field, $createFrom, $draft, false, false);
                Craft::$app->matrix->saveField($field, $draft);

                continue;
            }

            $field->copyValue($element, $draft);
        }

        $draft->title = $element->title;

        $draft->setCanonicalId(
            $createFrom->id
        );

        $draft->duplicateOf = $element;

        $copyEntriesSlugFromSourceToTarget = SettingRecord::findOne(
            ['name' => 'copy_entries_slug_from_source_to_target']
        );
        $isCopySlugEnabled = (bool) ($copyEntriesSlugFromSourceToTarget->value ?? false);

        if ($isCopySlugEnabled) {
            $draft->slug = $element->slug;
        }

        Craft::$app->elements->saveElement($draft, true, false, false);

        $this->markFieldsAsChanged($draft);

        $attributes = ['title'];

        if ($isCopySlugEnabled) {
            $attributes[] = 'slug';
        }
        $this->upsertChangedAttributes($draft, $attributes);

        return $draft;
    }

    /**
     * @throws InvalidFieldException
     * @throws \yii\db\Exception
     */
    private function markFieldsAsChanged(ElementInterface $element): void
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
                 * @var ElementQuery $matrixBlockQuery
                 */
                $matrixBlockQuery = $element->getFieldValue($field->handle);

                /**
                 * @var Element[] $blockElements
                 */
                $blockElements = $matrixBlockQuery->all();

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

    private function upsertChangedAttributes(ElementInterface $element, array $attributes): void
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
