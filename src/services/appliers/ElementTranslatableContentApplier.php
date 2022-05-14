<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\MatrixBlock;
use craft\errors\InvalidFieldException;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\fields\RadioButtons;
use craft\fields\Table;
use craft\redactor\Field as RedactorPluginField;
use \craft\services\Drafts as DraftRepository;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\datetime\DateTime;
use lilthq\craftliltplugin\exeptions\DraftNotFoundException;
use lilthq\craftliltplugin\records\I18NRecord;
use Throwable;

class ElementTranslatableContentApplier
{
    /**
     * @var DraftRepository
     */
    public $draftRepository;

    /**
     * @throws Throwable
     * @throws InvalidFieldException
     */
    public function apply(TranslationApplyCommand $translationApplyCommand): ElementInterface
    {
        $newAttributes = [];
        $i18NRecords = [];

        $content = $translationApplyCommand->getContent();

        $draft = $this->draftRepository->createDraft(
            $translationApplyCommand->getElement()->getIsDraft() ? Craft::$app->elements->getElementById(
                $translationApplyCommand->getElement()->getCanonicalId()
            ) : $translationApplyCommand->getElement(),
            Craft::$app->getUser()->getId(),
            sprintf(
                '%s [%s -> %s] ' . (new DateTime())->format('H:i:s'),
                $translationApplyCommand->getJob()->title,
                Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId(
                    (int)$translationApplyCommand->getJob()->sourceSiteId
                ),
                $translationApplyCommand->getTargetLanguage()
            ),
            $notes = null,
            $newAttributes,
            $provisional = false
        );

        $draftElement = Craft::$app->elements->getElementById(
            $draft->getId(),
            null,
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

        if (!empty($draftElement->slug)) {
            $draftElement->slug = $content['slug'];
        }

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

            $fieldDataKey = $fieldData->handle;

            if (
                $fieldData instanceof PlainText
                && isset($content[$fieldDataKey])
                && $fieldData->getIsTranslatable($draftElement)
            ) {
                $draftElement->setFieldValue($fieldData->handle, $content[$fieldDataKey]);

                continue;
            }

            if ($fieldData instanceof RedactorPluginField && $fieldData->getIsTranslatable($draftElement)) {
                $draftElement->setFieldValue($fieldData->handle, $content[$fieldDataKey]);

                continue;
            }

            if ($fieldData instanceof Table && $fieldData->getIsTranslatable($draftElement)) {
                $draftElement->setFieldValue($fieldData->handle, $content[$fieldDataKey]);

                continue;
            }

            if ($fieldData instanceof Matrix) {
                /**
                 * @var MatrixBlockQuery $fieldValue
                 */
                $matrixBlockQuery = $draftElement->getFieldValue($fieldData->handle);

                $serializedData = $field->serializeValue($matrixBlockQuery, $draftElement);


                /**
                 * @var MatrixBlock $block
                 */
                foreach ($matrixBlockQuery->all() as $block) {
                    foreach ($block->getFieldLayout()->getFields() as $blockField) {
                        $blockId = $block->getCanonicalId();

                        if ($blockField instanceof Table) {
                            $tableSource = $content[$fieldData->handle][$blockId]['fields'][$blockField->handle]['content'];
                            foreach ($blockField->columns as $column => $columnData) {
                                foreach ($tableSource as $rowId => $rows) {
                                    $tableSource[$rowId][$column] = $tableSource[$rowId][$columnData['handle']];
                                }
                            }
                            $content[$fieldData->handle][$blockId]['fields'][$blockField->handle]['content'] = $tableSource;

                            if(isset($content[$fieldData->handle][$blockId]['fields'][$blockField->handle]['columns'])) {
                                $columns = $content[$fieldData->handle][$blockId]['fields'][$blockField->handle]['columns'];
                                foreach ($blockField->columns as $column) {
                                    $translation = [
                                        'target' => $columns[$column['handle']],
                                        'source' => $column['heading'],
                                        'sourceSiteId' => $translationApplyCommand->getSourceSiteId(),
                                        'targetSiteId' => $translationApplyCommand->getTargetSiteId(),
                                    ];

                                    $translation['hash'] = md5(json_encode($translation));


                                    $record = new I18NRecord();
                                    $record->target = $translation['target'];
                                    $record->source = $translation['source'];
                                    $record->sourceSiteId = $translation['sourceSiteId'];
                                    $record->targetSiteId = $translation['targetSiteId'];
                                    $record->hash = $translation['hash'];

                                    $i18NRecords[$record->hash] = $record;
                                }
                            }

                            $content[$fieldData->handle][$blockId]['fields'][$blockField->handle] = $content[$fieldData->handle][$blockId]['fields'][$blockField->handle]['content'];
                        }

                        if ($blockField instanceof RadioButtons) {
                            $options = $blockField->options;

                            if (!isset(
                                $content[$fieldData->handle][$blockId]['fields'][$blockField->handle]
                            )) {
                                continue;
                            }

                            $optionsTranslated = $content[$fieldData->handle][$blockId]['fields'][$blockField->handle];

                            foreach ($options as $option) {
                                $translation = [
                                    'target' => $optionsTranslated[$option['value']],
                                    'source' => $option['label'],
                                    'sourceSiteId' => $translationApplyCommand->getSourceSiteId(),
                                    'targetSiteId' => $translationApplyCommand->getTargetSiteId(),
                                ];

                                $translation['hash'] = md5(json_encode($translation));


                                $record = new I18NRecord();
                                $record->target = $translation['target'];
                                $record->source = $translation['source'];
                                $record->sourceSiteId = $translation['sourceSiteId'];
                                $record->targetSiteId = $translation['targetSiteId'];
                                $record->hash = $translation['hash'];

                                $i18NRecords[$record->hash] = $record;
                            }

                            continue;
                        }
                    }
                }


                $contentWithoutIds = [$fieldData->handle => array_values($content[$fieldData->handle])];

                $i = 0;
                foreach ($serializedData as $key => $value) {
                    $serializedData[$key] = $this->merge(
                        $serializedData[$key],
                        $contentWithoutIds[$fieldData->handle][$i++]
                    );
                }

                $draftElement->setFieldValue($fieldData->handle, $serializedData);
            }
        }

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

        Craft::$app->elements->saveElement(
            $draftElement
        );

        return $draftElement;
    }

    private function merge(array $original, array $new): array
    {
        foreach ($new as $key => $newItem) {
            if (!array_key_exists(
                $key,
                $original
            )) { //TODO: looks like content can be empty? Is it change? || empty($original[$key])) {
                //TODO: log issue? How we can't have key in original?
                $original[$key] = $newItem;
                continue;
            }

            if (is_array($newItem)) {
                if (isset($original[$key]) && !is_array($original[$key])) {
                    continue;
                }

                $original[$key] = $this->merge($original[$key] ?? [], $newItem);

                continue;
            }

            $original[$key] = $newItem;
        }

        return $original;
        #return array_merge_recursive($original, $new);
    }
}
