<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers;

use Craft;
use craft\base\ElementInterface;
use craft\errors\InvalidFieldException;
use craft\services\Drafts as DraftRepository;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\datetime\DateTime;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\exceptions\DraftNotFoundException;
use lilthq\craftliltplugin\records\I18NRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\appliers\field\ApplyContentCommand;
use lilthq\craftliltplugin\services\appliers\field\FieldContentApplier;
use Throwable;

class ElementTranslatableContentApplier
{
    /**
     * @var DraftRepository
     */
    public $draftRepository;

    /**
     * @var FieldContentApplier
     */
    public $fieldContentApplier;

    /**
     * @throws Throwable
     * @throws InvalidFieldException
     */
    public function apply(TranslationApplyCommand $translationApplyCommand): ElementInterface
    {
        $newAttributes = [];
        $i18NRecords = [];

        $content = $translationApplyCommand->getContent();

        $draftElement = Craft::$app->elements->getElementById(
            $translationApplyCommand->getJob()->getElementVersionId(
                $translationApplyCommand->getElement()->getId()
            ),
            'craft\elements\Entry',
            Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage(
                $translationApplyCommand->getTargetLanguage()
            )
        );

        if (!$draftElement) {
            //TODO: handle?
            throw new DraftNotFoundException();
        }

        if (!empty($draftElement->title) && $draftElement->getIsTitleTranslatable()) {
            $draftElement->title = $content['title'];
        }

        # TODO: clarify should we translate slug or not
        #if (!empty($draftElement->slug)) {
        #    $draftElement->slug = $content['slug'];
        #}

        $fieldLayout = $draftElement->getFieldLayout();

        if ($fieldLayout === null) {
            //TODO: log issue
        }

        $fields = $fieldLayout ? $fieldLayout->getFields() : [];

        foreach ($fields as $field) {
            $fieldData = Craft::$app->fields->getFieldById((int)$field->id);

            if ($fieldData === null) {
                //TODO: log issue
                continue;
            }

            $command = new ApplyContentCommand(
                $draftElement,
                $fieldData,
                $content,
                $translationApplyCommand->getSourceSiteId(),
                $translationApplyCommand->getTargetSiteId(),
                $translationApplyCommand->getJob(),
                $translationApplyCommand->getTranslationRecord()
            );

            $result = $this->fieldContentApplier->apply(
                $command
            );

            if (!$result->isApplied()) {
                $translationApplyCommand->getTranslationRecord()->status = TranslationRecord::STATUS_NEEDS_ATTENTION;
                $translationApplyCommand->getTranslationRecord()->save();
            }

            if ($result->isApplied() && $result->getFieldValue()) {
                $draftElement->setFieldValue($field->handle, $result->getFieldValue());
            }

            $i18NRecords[] = $result->getI18nRecords();
        }

        $i18NRecords = !empty($i18NRecords) ? array_merge(...$i18NRecords) : [];

        if (!empty($i18NRecords)) {
            //SAVE I18NRecords TODO: move to repository
            $exists = I18NRecord::findAll([
                'hash' => array_keys($i18NRecords),
            ]);

            foreach ($exists as $exist) {
                unset($i18NRecords[$exist->hash]);
            }

            foreach ($i18NRecords as $i18NRecord) {
                $i18NRecord->save();
            }
        }

        /** @since setIsFresh in craft only since 3.7.14 */
        if (method_exists($draftElement, 'setIsFresh')) {
            $draftElement->setIsFresh();
        }

        Craft::$app
            ->elements
            ->saveElement(
                $draftElement,
                true,
                false,
                false
            );

        return $draftElement;
    }

    /**
     *
     * TODO: remove this
     *
     * @param TranslationApplyCommand $translationApplyCommand
     * @param array $newAttributes
     * @return ElementInterface|null
     * @throws Throwable
     *
     * @deprecated use \lilthq\craftliltplugin\services\handlers\CreateDraftHandler instead
     */
    public function createDraftElement(
        TranslationApplyCommand $translationApplyCommand,
        array $newAttributes
    ): ElementInterface {
        /** Element will be created from original one, we can't create draft from draft */
        $createFrom = $translationApplyCommand->getElement()->getIsDraft() ? Craft::$app->elements->getElementById(
            $translationApplyCommand->getElement()->getCanonicalId()
        ) : $translationApplyCommand->getElement();

        $draft = $this->draftRepository->createDraft(
            $createFrom,
            Craft::$app->getUser()->getId(),
            sprintf(
                '%s [%s -> %s] ' . (new DateTime())->format('H:i:s'),
                $translationApplyCommand->getJob()->title,
                Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId(
                    (int)$translationApplyCommand->getJob()->sourceSiteId
                ),
                $translationApplyCommand->getTargetLanguage()
            ),
            null,
            $newAttributes
        );

        $draftElement = Craft::$app->elements->getElementById(
            $draft->getId(),
            'craft\elements\Entry',
            Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage(
                $translationApplyCommand->getTargetLanguage()
            )
        );

        if (!$draftElement) {
            throw new DraftNotFoundException();
        }

        return $draftElement;
    }
}
