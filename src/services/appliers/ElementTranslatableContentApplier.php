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
use craft\errors\InvalidFieldException;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\redactor\Field as RedactorPluginField;
use craft\redactor\FieldData as RedactorPluginFieldData;
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
    public function apply(ElementInterface $element, Job $job, array $content, string $targetLanguage): bool
    {
        $newAttributes = [];

        $draft = $this->draftRepository->createDraft(
            $element,
            Craft::$app->getUser()->getId(),
            sprintf(
                '%s [%s -> %s] ' . (new DateTime())->format('H:i:s'),
                $job->title,
                Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId( (int) $job->sourceSiteId),
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
            }

            if ($fieldData instanceof RedactorPluginField && $fieldData->getIsTranslatable($draftElement)) {
                $draftElement->setFieldValue($fieldData->handle, $content[$fieldDataKey]);
            }

            if ($fieldData instanceof Matrix) {
                /**
                 * @var MatrixBlockQuery $fieldValue
                 */
                $matrixBlockQuery = $draftElement->getFieldValue($fieldData->handle);

                $serializedData = $field->serializeValue($matrixBlockQuery, $draftElement);
                $content[$fieldData->handle] = array_values($content[$fieldData->handle]);

                $i = 0;
                foreach ($serializedData as $key => $value) {
                    $serializedData[$key] = array_merge($serializedData[$key], $content[$fieldData->handle][$i++]);
                }

                $draftElement->setFieldValue($fieldData->handle, $serializedData);
            }
        }

        //$draft->
        Craft::$app->elements->saveElement(
            $draftElement
        );

        return true;
    }
}
