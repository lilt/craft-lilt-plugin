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
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\actions\JobEdit;
use lilthq\craftliltplugin\elements\db\JobQuery;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
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
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_IN_PROGRESS = 'in-progress';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_FAILED = 'failed';

    //TODO: add to database
    public $authorId;

    public $uid;
    public $title;
    public $liltJobId;
    public $status;
    public $sourceSiteId;
    public $sourceSiteLanguage;
    public $targetSiteIds;
    public $elementIds;
    public $files;
    public $dueDate;
    public $dateCreated;
    public $dateUpdated;

    private $_author;

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
                'targetSites' =>  Craftliltplugin::getInstance()->languageMapper->getSiteIdToLanguage(),
                'isRevision' =>  false,
                'elementType' =>  self::class,
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

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_NEW            => ['label' => 'New', 'color' => ''],
            self::STATUS_IN_PROGRESS    => ['label' => 'In Progress', 'color' => 'blue'],
            self::STATUS_SUBMITTED      => ['label' => 'Submitted', 'color' => 'purple'],
            self::STATUS_COMPLETE       => ['label' => 'Complete', 'color' => 'green'],
            self::STATUS_FAILED         => ['label' => 'Failed', 'color' => 'red'],
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
                'label' => 'All Orders',
                'criteria' => [],
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
                'key' => 'submitted',
                'label' => 'Submitted',
                'criteria' => [
                    'status' => [
                        self::STATUS_SUBMITTED
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
            'title'         => 'Title',
            'status'        => 'Status',
            'sourceSiteId'  => 'Site source',
            'dueDate'       => 'Due Date',
            'dateCreated'   => 'Created',
            'dateUpdated'   => 'Updated',
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
            'title'         => 'Title',
            'status'        => 'Status',
            'sourceSiteId'  => 'Site source',
            'targetSiteIds' => 'Target source',
            'dueDate'       => 'Due Date',
            'dateCreated'   => 'Created',
            'dateUpdated'   => 'Updated',
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'title',
            'status',
            'sourceSiteId',
            'targetSiteIds',
            'dueDate',
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

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'sourceSiteId':
                return
                    "<span class='source-language' data-icon='world'>"
                    . Craftliltplugin::getInstance()->languageMapper->getLanguageBySiteId((int)$this->sourceSiteId)
                    . "</span>";

            case 'status':
                return $this->getStatusHtml();
            case 'dueDate':
                return (new \DateTime($this->dueDate))->format(
                    Craft::$app->locale->getDateFormat('short', 'php')
                );
            case 'targetSiteIds':
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
            [['title','sourceSiteId','elementIds','dueDate','targetSiteIds'], 'required'],
        ];
    }
}
