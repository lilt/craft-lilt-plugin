<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\elements;

use Craft;
use craft\elements\Entry;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\models\TranslationModel;

/**
 * Customization of entry, to show the versions multi select
 */
class TranslateEntry extends Entry
{
    protected function tableAttributeHtml(string $attribute): string
    {
        $showEntryVersions = Craft::$app->getRequest()->getParam('showEntryVersions', false);

        if (!$showEntryVersions || $attribute !== 'drafts') {
            return parent::tableAttributeHtml($attribute);
        }

        if (!$this->hasEagerLoadedElements('drafts')) {
            return '';
        }

        $drafts = $this->getEagerLoadedElements('drafts');
        $options = [
            [
                'value' => base64_encode(json_encode(['elementId' => $this->id, 'draftId' => null])),
                'label' => 'Current',
                'data' => [
                    'draft-id' => null,
                    'draft-element-id' => $this->id
                ]
            ]
        ];

        foreach ($drafts as $draft) {
            $option = [];
            $option['value'] = base64_encode(json_encode(['elementId' => $this->id, 'draftId' => $draft->id]));
            $option['label'] = $draft->draftName;
            $option['data'] = [
                'draft-id' => $draft->draftId,
                'draft-element-id' => $this->id
            ];

            $options[] = $option;
        }

        return Cp::selectFieldHtml([
            'options' => $options,
            'class' => 'select-element-version',
            'data' => [
                'element-id' => $this->id,
            ]
        ]);
    }

    public function getHtmlAttributes(
        string $context
    ): array {
        $attributes = parent::getHtmlAttributes($context);
        $translations = Craftliltplugin::getInstance()->translationRepository->findInProgressByElementId($this->id);

        if (!empty($translations)) {
            $jobIds = array_map(
                static function (TranslationModel $translation) {
                    return $translation->jobId;
                },
                $translations
            );

            $attributes['data-has-active-lilt-job'] = true;
            $attributes['data-active-lilt-job-ids'] = json_encode(array_unique($jobIds));
            $attributes['data-active-lilt-job-url'] = UrlHelper::cpUrl('/admin/craft-lilt-plugin/jobs', [
                'statuses' => ['failed', 'in-progress', 'ready-for-review', 'ready-to-publish'],
                'elementIds' => array_unique($jobIds)
            ]);
        }

        return $attributes;
    }
}
