<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\elements\db;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use lilthq\craftliltplugin\elements\Translation;

class TranslationQuery extends ElementQuery
{
    public string|array|null $status;
    public mixed $dateCreated = null;
    public mixed $dateUpdated = null;

    public $jobId;

    public function status($value): self
    {
        $this->status = $value;

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function afterPopulate(array $elements): array
    {
        //TODO: move to repository
        $translatedDraftIds = array_map(function (Translation $element) {
            return $element->translatedDraftId;
        }, $elements);
        /**
         * @var Entry[] $translatedDraftsMapped
         */
        $translatedDraftsMapped = [];
        foreach ($translatedDraftIds as $draftId) {
            $translatedDraftsMapped[$draftId] = Craft::$app->elements->getElementById((int)$translatedDraftIds[0]);
        }

        return array_map(
            static function (Translation $element) use ($translatedDraftsMapped) {
                if (!empty($element->translatedDraftId)) {
                    $element->translatedDraftId = (int)$element->translatedDraftId;
                }
                if (!empty($element->elementId)) {
                    $element->elementId = (int)$element->elementId;
                }
                if (!empty($element->sourceSiteId)) {
                    $element->sourceSiteId = (int)$element->sourceSiteId;
                }
                if (!empty($element->targetSiteId)) {
                    $element->sourceSiteId = (int)$element->sourceSiteId;
                }
                if (!empty($element->jobId)) {
                    $element->jobId = (int)$element->jobId;
                }

                if (isset($translatedDraftsMapped[$element->translatedDraftId])) {
                    $element->cpEditUrl = $translatedDraftsMapped[$element->translatedDraftId]->getCpEditUrl();
                    $element->targetDraft = $translatedDraftsMapped[$element->translatedDraftId];
                }

                return $element;
            },
            $elements
        );
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('lilt_translations');

        /** GET TITLE */
        $this->query->innerJoin(Table::CONTENT . ' content', [
            'and',
            '[[content.elementId]] = [[lilt_translations.elementId]]',
            '[[content.siteId]] = [[lilt_translations.sourceSiteId]]'
        ]);

        $this->query->innerJoin(Table::SITES . ' sites', [
            'and',
            '[[sites.id]] = [[lilt_translations.targetSiteId]]'
        ]);

        $this->query->select([
            'content.title',
            'sites.name AS targetSiteName',
            'sites.language AS targetSiteLanguage',
            'lilt_translations.jobId',
            'lilt_translations.elementId',
            'lilt_translations.versionId',
            'lilt_translations.translatedDraftId',
            'lilt_translations.sourceSiteId',
            'lilt_translations.targetSiteId',
            'lilt_translations.sourceContent',
            'lilt_translations.targetContent',
            'lilt_translations.lastDelivery',
            'lilt_translations.status',
            'lilt_translations.connectorTranslationId',
            'lilt_translations.dateCreated',
            'lilt_translations.dateUpdated',
        ]);

        if ($this->status) {
            if (is_array($this->status)) {
                $this->subQuery->andWhere(['in', '[[lilt_translations.status]]', $this->status]);
            } elseif ($this->status !== '*') {
                $this->subQuery->andWhere('[[lilt_translations.status]] = :status', [':status' => $this->status]);
            }
        }

        if ($this->jobId) {
            $this->subQuery->andWhere('[[lilt_translations.jobId]] = :jobId', [':jobId' => $this->jobId]);
        }

        return parent::beforePrepare();
    }

    public function prepare($builder): Query
    {
        parent::prepare($builder);

        //Remove custom fields from subquery, since it is exist only in query
        if (isset($this->subQuery->orderBy['targetSiteName'])) {
            unset($this->subQuery->orderBy['targetSiteName']);
        }

        if (isset($this->subQuery->orderBy['targetSiteLanguage'])) {
            unset($this->subQuery->orderBy['targetSiteLanguage']);
        }

        if (isset($this->subQuery->orderBy['title'])) {
            unset($this->subQuery->orderBy['title']);
        }

        return $this->query;
    }

    protected function statusCondition(string $status): bool
    {
        return array_key_exists($status, Translation::statuses());
    }
}
