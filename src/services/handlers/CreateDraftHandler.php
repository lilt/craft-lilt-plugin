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
            //TODO: it is not expected to reach, but it is possible. Investigation herer, why user id is null?
            Craft::error(
                "Can't get user from current session with Craft::\$app->user->getId(),"
                . "please check you app configuration!"
            );
            $creatorId = $createFrom->authorId;
        }

        $draft = Craft::$app->drafts->createDraft(
            $createFrom,
            $creatorId ?? 0, //TODO: not best but one of the ways. Need to check why user can have nullable id?
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
        $fields = $fieldLayout ? $fieldLayout->getCustomFields() : [];

        foreach ($fields as $field) {
            $field->copyValue($element, $draft);
        }

        $draft->title = $element->title;

        $draft->duplicateOf = $element;
        $draft->mergingCanonicalChanges = true;

        /**
         * TODO: for some reason we have duplicate error here.
         *  Craft tries to create same block. Need investigation here.
         */
        $draftId = $draft->draftId;
        $draft->draftId = null;
        $draft->afterPropagate(false);
        $draft->draftId = $draftId;

        $copyEntriesSlugFromSourceToTarget = SettingRecord::findOne(
            ['name' => 'copy_entries_slug_from_source_to_target']
        );
        $isCopySlugEnabled = (bool) ($copyEntriesSlugFromSourceToTarget->value ?? false);

        if ($isCopySlugEnabled) {
            $draft->slug = $element->slug;
        }

        Craft::$app->elements->saveElement($draft);

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
        $fields = $fieldLayout ? $fieldLayout->getCustomFields() : [];

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
