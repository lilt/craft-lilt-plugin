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
use craft\helpers\UrlHelper;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\actions\JobEdit;
use lilthq\craftliltplugin\elements\db\JobQuery;
use lilthq\craftliltplugin\models\TranslationModel;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;

/**
 *
 * @property-read null|false $author
 * @property-read string $sidebarHtml
 * @property-read string $elementIdsAsString
 * @property-read string $statusHtml
 */
class Job extends Element
{
    public const STATUS_NEW = 'new';
    public const STATUS_DRAFT = 'draft';
    #public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_IN_PROGRESS = 'in-progress';
    public const STATUS_READY_FOR_REVIEW = 'ready-for-review';
    public const STATUS_READY_TO_PUBLISH = 'ready-to-publish';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_FAILED = 'failed';


    public $uid;
    public $authorId;
    public $title;
    public $liltJobId;
    public $status;
    public $sourceSiteId;
    public $sourceSiteLanguage;
    public $targetSiteIds;
    public $elementIds;
    public $versions;
    public $dueDate;
    public $translationWorkflow;
    public $dateCreated;
    public $dateUpdated;

    // @codingStandardsIgnoreStart
    private $_author;
    private $_elements;
    private $_translations;
    // @codingStandardsIgnoreEnd

    public function beforeDelete(): bool
    {
        JobRecord::deleteAll(['id' => $this->id]);
        return parent::beforeDelete();
    }

    public function getSidebarHtml(): string
    {
        $html = parent::getSidebarHtml();
        //TODO: add status here
        return $html;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws Exception
     * @throws LoaderError
     */
    public function getEditorHtml(): string
    {
        return Craft::$app->getView()->renderTemplate(
            'craft-lilt-plugin/_components/job/_form.twig',
            [
                'element' => $this,
                'formActionUrl' => UrlHelper::cpUrl('craft-lilt-plugin/job/edit/' . $this->getId()),
                'availableSites' => Craftliltplugin::getInstance()->languageMapper->getAvailableSitesForFormField(),
                'targetSites' => Craftliltplugin::getInstance()->languageMapper->getSiteIdToLanguage(),
                'isRevision' => false,
                'elementType' => self::class,
            ]
        );
    }

    public function getElementIds(): array
    {
        if (empty($this->elementIds)) {
            return [];
        }

        if (is_array($this->elementIds)) {
            return $this->elementIds;
        }

        return json_decode($this->elementIds, true) ?? [];
    }


    public function getVersions(): array
    {
        if (empty($this->versions)) {
            return [];
        }

        if (is_array($this->versions)) {
            return $this->versions;
        }

        return json_decode($this->versions, true) ?? [];
    }

    public function getVersionsAsString(): string
    {
        return json_encode($this->getVersions() ?? []);
    }

    public function getElementIdsAsString(): string
    {
        return json_encode($this->getElementIds() ?? []);
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Job';
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return false;
    }

    public function afterDelete(): bool
    {
        JobRecord::deleteAll(['id' => $this->id]);
        parent::afterDelete();
        return true;
    }

    public function isInstantFlow(): bool
    {
        return strtolower($this->translationWorkflow) === strtolower(
            SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT
        );
    }
    public function isVerifiedFlow(): bool
    {
        return strtolower($this->translationWorkflow) === strtolower(
            SettingsResponse::LILT_TRANSLATION_WORKFLOW_VERIFIED
        );
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_NEW => ['label' => 'New', 'color' => 'turquoise'],
            self::STATUS_DRAFT => ['label' => 'Draft', 'color' => ''],
            #self::STATUS_SUBMITTED => ['label' => 'Submitted', 'color' => 'purple'],
            self::STATUS_IN_PROGRESS => ['label' => 'In Progress', 'color' => 'blue'],
            self::STATUS_READY_FOR_REVIEW => ['label' => 'Ready for review', 'color' => 'yellow'],
            self::STATUS_READY_TO_PUBLISH => ['label' => 'Ready to publish', 'color' => 'purple'],
            self::STATUS_COMPLETE => ['label' => 'Complete', 'color' => 'green'],
            self::STATUS_FAILED => ['label' => 'Failed', 'color' => 'red'],
        ];
    }

    public function getStatusHtml(): string
    {
        $label = self::statuses()[$this->status]['label'] ?? self::statuses()[$this->status];
        $color = self::statuses()[$this->status]['color'] ?? '';

        return "<span class='status {$color}'></span>" . $label;
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return 'Job';
    }

    public static function find(): ElementQueryInterface
    {
        return new JobQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        return [
            [
                'key' => 'all',
                'label' => 'All Jobs',
                'criteria' => [],
                'defaultSort' => ['dateCreated', 'desc']
            ],
            [
                'key' => 'draft',
                'label' => 'Draft',
                'criteria' => [
                    'status' => [
                        self::STATUS_DRAFT
                    ]
                ],
                'defaultSort' => ['dateCreated', 'desc']
            ],
            [
                'key' => 'new',
                'label' => 'New',
                'criteria' => [
                    'status' => [
                        self::STATUS_NEW
                    ]
                ],
                'defaultSort' => ['dateCreated', 'desc']
            ],
            /* [
                'key' => 'submitted',
                'label' => 'Submitted',
                'criteria' => [
                    'status' => [
                        self::STATUS_SUBMITTED
                    ]
                ],
                'defaultSort' => ['dateCreated', 'desc']
            ], */
            [
                'key' => 'in-progress',
                'label' => 'In progress',
                'criteria' => [
                    'status' => [
                        self::STATUS_IN_PROGRESS
                    ]
                ],
                'defaultSort' => ['dateCreated', 'desc']
            ],
            [
                'key' => 'ready-for-review',
                'label' => 'Ready for review',
                'criteria' => [
                    'status' => [
                        self::STATUS_READY_FOR_REVIEW
                    ]
                ],
                'defaultSort' => ['dateCreated', 'desc']
            ],
            [
                'key' => 'ready-to-publish',
                'label' => 'Ready to publish',
                'criteria' => [
                    'status' => [
                        self::STATUS_READY_TO_PUBLISH
                    ]
                ],
                'defaultSort' => ['dateCreated', 'desc']
            ],
            [
                'key' => 'complete',
                'label' => 'Complete',
                'criteria' => [
                    'status' => [
                        self::STATUS_COMPLETE
                    ]
                ],
                'defaultSort' => ['dateCreated', 'desc']
            ],
            [
                'key' => 'failed',
                'label' => 'Failed',
                'criteria' => [
                    'status' => [
                        self::STATUS_FAILED
                    ]
                ],
                'defaultSort' => ['dateCreated', 'desc']
            ],
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => 'Title',
            'status' => 'Status',
            'sourceSiteId' => 'Site source',
            #'dueDate' => 'Due Date',
            'dateCreated' => 'Created',
            'dateUpdated' => 'Updated',
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        return [
            'title',
            'id',
            'status'
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => 'Title',
            'status' => 'Status',
            'sourceSiteId' => 'Site source',
            'targetSiteIds' => 'Target source',
            #'dueDate' => 'Due Date',
            'dateCreated' => 'Created',
            'dateUpdated' => 'Updated',
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'title',
            'status',
            'sourceSiteId',
            'targetSiteIds',
            #'dueDate',
            'dateCreated',
            'dateUpdated',
        ];
    }

    # TODO: not working T_T
    #public function getUiLabel(): string
    #{
    #    return "<a href='".UrlHelper::cpUrl('craft-lilt-plugin/job/' . $this->id)."'>$this->title</a>";
    #}

    public function getTargetSiteIds(): array
    {
        if ($this->targetSiteIds === null) {
            return [];
        }

        if (is_array($this->targetSiteIds)) {
            return $this->targetSiteIds;
        }

        return json_decode($this->targetSiteIds, true) ?? [];
    }

    public function getSourceSiteIdHtml(): string
    {
        return
            "<span class='source-language' data-icon='world'>"
            . Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId((int)$this->sourceSiteId)
            . "</span>";
    }

    public function getTargetSiteIdsHtml(): string
    {
        $html = '<ul class="target-languages-list">';

        $targetSites = json_decode($this->targetSiteIds, true);
        if (isset($targetSites[0]) && is_array($targetSites[0]) && isset($targetSites[0]['id'])) {
            $targetSites = array_column($targetSites, 'id');
        }

        $languages = Craftliltplugin::getInstance()->languageMapper->getLanguagesBySiteIds(
            $targetSites
        );

        foreach ($languages as $language) {
            $html .= "<li><span data-icon='world'>{$language}</span></li>";
        }
        $html .= '</ul>';

        return $html;
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'sourceSiteId':
                return $this->getSourceSiteIdHtml();

            case 'status':
                return $this->getStatusHtml();
            //TODO: due date not in use at the moment
            //case 'dueDate':
            //    return $this->dueDate->format(
            //        Craft::$app->locale->getDateFormat('short', 'php')
            //    );
            case 'targetSiteIds':
                return $this->getTargetSiteIdsHtml();
        }

        return parent::tableAttributeHtml($attribute);
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function isEditable(): bool
    {
        return true;
    }

    public function getUrl(): string
    {
        return CraftliltpluginParameters::JOB_EDIT_PATH . '/' . $this->id;
    }

    /**
     * @return TranslationModel[]
     */
    public function getTranslations(): array
    {
        if (!empty($this->_elements)) {
            return $this->_elements;
        }

        $this->_translations = Craftliltplugin::getInstance()->translationRepository->findByJobId($this->id);

        //TODO: it should be not here, it is a logic
        $readyToPublish = true;

        foreach ($this->_translations as $translation) {
            if ($translation->status !== TranslationRecord::STATUS_READY_TO_PUBLISH) {
                $readyToPublish = false;
                break;
            }
        }

        if ($readyToPublish) {
            $this->status = Job::STATUS_READY_TO_PUBLISH;
            Craft::$app->elements->saveElement($this);
        }

        return $this->_translations;
    }

    public function getElementsMappedById(): array
    {
        if (!empty($this->_elements)) {
            return $this->_elements;
        }

        $this->_elements = [];

        //TODO: maybe other way to select, not one by one?
        $elements = array_map(function (int $id) {
            return Craft::$app->elements->getElementById($id);
        }, $this->getElementIds());

        foreach ($elements as $element) {
            $this->_elements[$element->id] = $element;
        }

        return $this->_elements;
    }

    public function getCpEditUrl()
    {
        return CraftliltpluginParameters::JOB_EDIT_PATH . '/' . $this->id;
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

        #$actions[] = LiltJobSetStatus::class;

        return $actions;
    }

    public function getIsEditable(): bool
    {
        #return \Craft::$app->user->checkPermission('edit-product:'.$this->getType()->id);
        return true;
    }

    #public function getFieldLayout()
    #{
    #    return \Craft::$app->fields->getLayoutByType(Job::class);
    #}

    public function getAuthor()
    {
        if ($this->_author === null) {
            if ($this->authorId === null) {
                return null;
            }

            if (($this->_author = Craft::$app->getUsers()->getUserById($this->authorId)) === null) {
                $this->_author = false;
            }
        }

        return $this->_author ?: null;
    }

    public function rules(): array
    {
        return [
            [['title', 'sourceSiteId', 'elementIds',/* 'dueDate',*/ 'targetSiteIds'], 'required'],
        ];
    }
}
