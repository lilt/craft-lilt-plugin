<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
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
use lilthq\craftliltplugin\elements\Job;
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
    public function apply(ElementInterface $element, Job $job, array $content, string $targetLanguage): ElementInterface
    {
        $newAttributes = [];

        $draft = $this->draftRepository->createDraft(
            $element->getIsDraft() ? Craft::$app->elements->getElementById($element->getCanonicalId()) : $element,
            Craft::$app->getUser()->getId(),
            sprintf(
                '%s [%s -> %s] ' . (new DateTime())->format('H:i:s'),
                $job->title,
                Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId((int)$job->sourceSiteId),
                $targetLanguage
            ),
            $notes = null,
            $newAttributes,
            $provisional = false
        );

        $draftElement = Craft::$app->elements->getElementById(
            $draft->getId(),
            null,
            Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage($targetLanguage)
        );

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
                        if ($blockField instanceof Table) {
                            $tableSource = $content[$fieldData->handle][$block->getCanonicalId()]['fields'][$blockField->handle];
                            foreach ($blockField->columns as $column => $columnData) {
                                foreach ($tableSource as $rowId => $rows) {
                                    $tableSource[$rowId][$column] = $tableSource[$rowId][$columnData['handle']];
                                }
                            }
                            $content[$fieldData->handle][$block->getCanonicalId()]['fields'][$blockField->handle] = $tableSource;
                        }

                        if ($blockField instanceof RadioButtons) {
                            $options = $blockField->options;

                            if(!isset($content[$fieldData->handle][$block->getCanonicalId()]['fields'][$blockField->handle])) {
                                continue;
                            }

                            $optionsTranslated = $content[$fieldData->handle][$block->getCanonicalId()]['fields'][$blockField->handle];

                            $translations = [];
                            foreach ($options as $option) {
                                $translations[] = [
                                    'target' => $optionsTranslated[$option['value']],
                                    'source' => $option['label'],
                                ];
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

                $normilized = $field->normalizeValue($serializedData);

                $draftElement->setFieldValue($fieldData->handle, $serializedData);

                continue;
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
                if(isset($original[$key]) && !is_array($original[$key])) {
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
