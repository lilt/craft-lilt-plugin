<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\elements;

use craft\elements\Entry;
use craft\helpers\Cp;

/**
 * Customization of entry, to show the versions multi select
 */
class TranslateEntry extends Entry
{
    protected function tableAttributeHtml(string $attribute): string
    {
        if ($attribute === 'drafts') {
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

        return parent::tableAttributeHtml($attribute);
    }
}
