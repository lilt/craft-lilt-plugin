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
use Exception;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\datetime\DateTime;
use lilthq\craftliltplugin\records\TranslationRecord;

class TranslationModel extends Model
{
    use TranslationModelTrait;

    public $id;
    public $uid;
    public $jobId;
    public $elementId;
    public $versionId;
    public $translatedDraftId;
    public $sourceSiteId;
    public $targetSiteId;
    public $sourceContent;
    public $targetContent;
    public $lastDelivery;
    public $status;
    public $connectorTranslationId;
    public $dateCreated;
    public $dateUpdated;

    /* public function init()
     {
         parent::init();

         if(!empty($this->sourceContent)) {
             $this->sourceContent = json_decode($this->sourceContent, false);
         }
         if(!empty($this->targetContent)) {
             $this->targetContent = json_decode($this->targetContent, false);
         }
     } */

    public function getSourceContentValues(): array
    {
        if (!empty($this->sourceContent)) {
            return $this->getContentValues($this->sourceContent);
        }

        return [];
    }

    public function getTargetContentValues(): array
    {
        if (!empty($this->targetContent)) {
            return $this->getContentValues($this->targetContent);
        }

        return [];
    }

    public function getContentValues(array $items, string $prefix = ''): array
    {
        $result = [];
        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->getContentValues($value, $prefix . $key . '.'));
            } else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

    public function getElementUrl(): ?string
    {
        if ($this->versionId === null) {
            $element = Craft::$app->elements->getElementById($this->elementId, null, $this->sourceSiteId);

            if ($element === null) {
                //element removed, we don't have any link
                return null;
            }

            return $element->getUrl();
        }

        $element = Craft::$app->elements->getElementById($this->versionId, null, $this->sourceSiteId);

        $token = Craft::$app->tokens->createToken([
            "preview/preview",
            [
                'elementType' => get_class($element),
                'sourceId' => $element->getCanonicalId(),
                'canonicalId' => $element->getCanonicalId(),
                'draftId' => $element->draftId,
                'siteId' => $this->sourceSiteId,
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

    /**
     * @throws Exception
     */
    public function getLastDeliveryFormatted(): ?string
    {
        return (new DateTime($this->lastDelivery))->format(
            Craft::$app->locale->getDateFormat('short', 'php')
        );
    }

    public function getDraftEditUrl(): ?string
    {
        if ($this->translatedDraftId === null) {
            //TODO: handle when draft is not exist yet
            return '';
        }
        //TODO: is it fine to do in foreach?
        $element = Craft::$app->elements->getElementById($this->translatedDraftId);

        if (!$element) {
            $element = Craft::$app->elements->getElementById($this->elementId);
        }

        if ($element === null) {
            //TODO: handle
            return null;
        }

        return UrlHelper::urlWithParams($element->getCpEditUrl(), [
            'site' =>
                Craftliltplugin::getInstance()->languageMapper->getHandleBySiteId($this->targetSiteId)
        ]);
    }

    public function getSourceLocale(): ?string
    {
        return Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId($this->sourceSiteId);
    }

    public function getTargetLocale(): ?string
    {
        return Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId($this->targetSiteId);
    }

    public function getStatusLabel(): string
    {
        switch ($this->status) {
            case TranslationRecord::STATUS_READY_TO_PUBLISH:
                return 'Ready to publish';
            case TranslationRecord::STATUS_NEW:
                return 'New';
            case TranslationRecord::STATUS_IN_PROGRESS:
                return 'In progress';
            case TranslationRecord::STATUS_READY_FOR_REVIEW:
                return 'Ready for review';
            case TranslationRecord::STATUS_PUBLISHED:
                return 'Published';
            case TranslationRecord::STATUS_FAILED:
                return 'Failed';
            default:
                return '';
        }
    }

    public function getStatusColor(): string
    {
        switch ($this->status) {
            case TranslationRecord::STATUS_IN_PROGRESS:
                return 'blue';
            case TranslationRecord::STATUS_READY_TO_PUBLISH:
                return 'purple';
            case TranslationRecord::STATUS_READY_FOR_REVIEW:
                return 'yellow';
            case TranslationRecord::STATUS_PUBLISHED:
                return 'green';
            case TranslationRecord::STATUS_FAILED:
                return 'red';
            case TranslationRecord::STATUS_NEW:
            default:
                return '';
        }
    }
}
