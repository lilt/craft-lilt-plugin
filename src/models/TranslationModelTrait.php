<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\models;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\datetime\DateTime;
use lilthq\craftliltplugin\records\TranslationRecord;

trait TranslationModelTrait
{
    /**
     * @return string|null
     */
    public function getPreviewUrl(): ?string
    {
        if ($this->translatedDraftId === null) {
            $element = Craft::$app->elements->getElementById($this->elementId, null, $this->targetSiteId);

            if ($element === null) {
                //element removed, we don't have any link
                return null;
            }

            return $element->getUrl();
        }
        $element = Craft::$app->elements->getElementById($this->translatedDraftId, null, $this->targetSiteId);

        if (!$element) {
            $element = Craft::$app->elements->getElementById($this->elementId, null, $this->targetSiteId);

            if ($element === null) {
                //TODO: handle
                return null;
            }

            return $element->getUrl();
        }

        $token = Craft::$app->tokens->createToken([
            "preview/preview",
            [
                'elementType' => get_class($element),
                'sourceId' => $element->getCanonicalId(),
                'canonicalId' => $element->getCanonicalId(),
                'draftId' => $element->draftId,
                'siteId' => $this->targetSiteId,
            ]
        ]);

        if ($element->getUrl() === null) {
            return null;
        }

        return UrlHelper::urlWithParams(
            $element->getUrl(),
            ['token' => $token]
        );
    }
}
