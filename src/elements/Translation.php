<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\actions\JobEdit;
use lilthq\craftliltplugin\elements\db\TranslationQuery;
use lilthq\craftliltplugin\models\TranslationModelTrait;
use lilthq\craftliltplugin\records\TranslationRecord;

/**
 *
 * @property-read null|false $author
 * @property-read string $sidebarHtml
 *
 * @property-read string $targetSiteIdHtml
 * @property-read string $sourceSiteIdHtml
 * @property-read string $statusHtml
 */
class Translation extends Element
{
    use TranslationModelTrait;

    public $id;
    public $uid;
    public $title;
    public $status;

    public $jobId;
    public $elementId;
    public $versionId;
    public $translatedDraftId;
    public $sourceSiteId;
    public $targetSiteId;
    public $targetSiteName;
    public $targetSiteLanguage;
    public $sourceContent;
    public $targetContent;
    public $lastDelivery;
    public $connectorTranslationId;

    public $dateCreated;
    public $dateUpdated;

    public $cpEditUrl;

    /**
     * @var Entry
     */
    public $targetDraft;

    public static function hasContent(): bool
    {
        return false;
    }

    public static function hasTitles(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Translation';
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return 'Translations';
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }


    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            TranslationRecord::STATUS_IN_PROGRESS => ['label' => 'In Progress', 'color' => 'blue'],
            TranslationRecord::STATUS_READY_FOR_REVIEW => ['label' => 'Ready for review', 'color' => 'yellow'],
            TranslationRecord::STATUS_READY_TO_PUBLISH => ['label' => 'Ready to publish', 'color' => 'purple'],
            TranslationRecord::STATUS_PUBLISHED => ['label' => 'Published', 'color' => 'green'],
            TranslationRecord::STATUS_FAILED => ['label' => 'Failed', 'color' => 'red'],
        ];
    }

    public function getIsPublished(): bool
    {
        return $this->status === TranslationRecord::STATUS_PUBLISHED;
    }

    public function getIsInProgress(): bool
    {
        return $this->status === TranslationRecord::STATUS_IN_PROGRESS;
    }

    public function getIsReviewed(): bool
    {
        return $this->getIsPublished() || $this->status === TranslationRecord::STATUS_READY_TO_PUBLISH;
    }

    public function getStatusHtml(): string
    {
        $label = self::statuses()[$this->status]['label'] ?? self::statuses()[$this->status];
        $color = self::statuses()[$this->status]['color'] ?? '';

        return "<span 
                    class='status translation-status {$color}'
                    data-id='{$this->id}' 
                    data-status='{$this->status}'
                    data-is-published='{$this->getIsPublished()}'
                    data-is-reviewed='{$this->getIsReviewed()}'
                    data-is-in-progress='{$this->getIsInProgress()}'
                    data-title='{$this->title}'>
                </span>" . $label;
    }

    public static function find(): ElementQueryInterface
    {
        return new TranslationQuery(static::class);
    }

    protected static function defineActions(string $source = null): array
    {
        $elementsService = Craft::$app->getElements();

        $actions[] = $elementsService->createAction([
            'type' => JobEdit::class,
            'label' => Craft::t('app', 'Edit job'),
        ]);

        $actions[] = [
            'type' => Delete::class,
        ];

        return $actions;
    }

    public function getStatus()
    {
        return $this->status;
    }

    protected static function defineSearchableAttributes(): array
    {
        return [
            'title',
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => 'Title',
            'status' => 'Status',
            'targetSiteLanguage' => 'Target source',
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => 'Title',
            'status' => 'Status',
            'targetSiteLanguage' => 'Target source',
            'actions' => 'Actions',
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'title',
            'status',
            'sourceSiteId',
            'targetSiteId',
            'targetSiteLanguage',
            'dateCreated',
            'dateUpdated',
            'actions',
        ];
    }

    public function toJson(): string
    {
        return json_encode([
            'draftId' => $this->translatedDraftId,
            'translationId' => $this->id,
            'sourceContent' => !empty($this->sourceContent) ? base64_encode($this->sourceContent) : null,
            'sourceSiteId' => $this->sourceSiteId,
            'targetSiteId' => $this->targetSiteId,
        ]);
    }

    public function getActionsHtml(): string
    {
        $dataSourceContent = '';
        if (!empty($this->sourceContent)) {
            $dataSourceContent = 'data-source-content="' . base64_encode($this->sourceContent) . '"';
        }

        return '
                <span 
                    class="lilt-review-translation" 
                    title="Review" 
                    data-json="' . base64_encode($this->toJson()) . '"
                    ' . $dataSourceContent . '
                    data-id="' . $this->id . '" data-title="' . $this->title . '" 
                    data-icon="view" 
                    style="margin-right: 5px;color: #0b69a3;cursor: pointer;font-size: 14pt;">
                </span>
                <a 
                    href="' . $this->getPreviewUrl() . '" 
                    title="Visit webpage" rel="noopener" 
                    target="_blank" 
                    data-icon="world" 
                    style="font-size: 14pt;">
               </a> ';
    }

    public function getTargetSiteIdHtml(): string
    {
        return
            "<span class='source-language' data-icon='world'>"
            . Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId((int)$this->targetSiteId)
            . "</span>";
    }

    public function getTargetSiteNameHtml(): string
    {
        return
            "<span class='source-language' data-icon='world'>"
            . $this->targetSiteName
            . "</span>";
    }

    public function getSourceSiteIdHtml(): string
    {
        return
            "<span class='source-language' data-icon='world'>"
            . Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId((int)$this->sourceSiteId)
            . "</span>";
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'sourceSiteId':
                return $this->getSourceSiteIdHtml();
            case 'status':
                return $this->getStatusHtml();
            case 'targetSiteId':
                return $this->getTargetSiteIdHtml();
            case 'actions':
                return $this->getActionsHtml();
            case 'targetSiteName':
                return $this->getTargetSiteNameHtml();
        }

        return parent::tableAttributeHtml($attribute);
    }

    public static function sources(string $context = null): array
    {
        return [
            [
                'key' => '*',
                'label' => 'All Translations',
                'criteria' => []
            ],
        ];
    }

    public function isEditable(): bool
    {
        return true;
    }

    public function getCpEditUrl(): ?string
    {
        return $this->cpEditUrl;
    }


    public function getIsEditable(): bool
    {
        return true;
    }

    public function getHtmlAttributes(string $context): array
    {
        return [
            'data-target-site-language' => $this->targetSiteLanguage,
            'data-target-site-id' => $this->targetSiteId,
            'data-translated-draft-id' => $this->translatedDraftId,
        ];
    }
}
