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

class TranslationModel extends Model
{
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
        return $this->getContentValues($this->sourceContent);
    }

    public function getTargetContentValues(): array
    {
        return $this->getContentValues($this->targetContent);
    }

    public function getContentValues(array $items, string $prefix = ''): array
    {
        $result = array();
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
        $draft = Craft::$app->elements->getElementById($this->translatedDraftId, null, $this->sourceSiteId);

        if(!$draft) {
            $draft = Craft::$app->elements->getElementById($this->elementId, null, $this->sourceSiteId);
        }

        return $draft->getUrl();

        /* TODO: not working for default site? We can't preview disabled entries for default site?
        $draft = Craft::$app->elements->getElementById($this->translatedDraftId, null, $this->sourceSiteId);
        $element = Craft::$app->elements->getElementById($this->elementId, null, $this->sourceSiteId);

        $preview = [
            'elementType' => get_class($element),
            'sourceId' => $element->getId(),
            'draftId' => $draft->draftId,
            'siteId' => $this->targetSiteId,
            'template' => 'blog/_entry',
        ];

        $token = Craft::$app->tokens->createToken([
            "preview/preview",
            $preview
        ]);

        return UrlHelper::urlWithParams(
            $element->getUrl(),
            ['token' => $token]
        );
        */
    }

    public function getLastDeliveryFormatted(): ?string
    {
        return (new DateTime($this->lastDelivery))->format(Craft::$app->locale->getDateFormat('short', 'php'));
    }

    public function getPreviewUrl(): ?string
    {
        if ($this->translatedDraftId === null) {
            //TODO: handle when draft is not exist yet
            return null;
        }

        $element = Craft::$app->elements->getElementById($this->translatedDraftId, null, $this->targetSiteId);

        if (!$element) {
            $element = Craft::$app->elements->getElementById($this->elementId, null, $this->targetSiteId);

            return $element->getUrl();
        }

        if ($element === null) {
            //TODO: handle
            return null;
        }

        $token = Craft::$app->tokens->createToken([
                "preview/preview",
                [
                    'elementType' => get_class($element),
                    'sourceId' => $element->getCanonicalId(),
                    'draftId' => $element->draftId,
                    'siteId' => $this->targetSiteId,
                ]
            ]);

        return UrlHelper::urlWithParams(
            $element->getUrl(),
            ['token' => $token]
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
