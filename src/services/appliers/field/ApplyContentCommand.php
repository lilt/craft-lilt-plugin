<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\TranslationRecord;

class ApplyContentCommand
{
    /**
     * @var ElementInterface
     */
    private $element;

    /**
     * @var FieldInterface
     */
    private $field;

    /**
     * @var array
     */
    private $content;

    /**
     * @var int
     */
    private $sourceSiteId;

    /**
     * @var int
     */
    private $targetSiteId;

    /**
     * @var Job
     */
    private $job;

    /**
     * @var TranslationRecord
     */
    private $translationRecord;

    public function __construct(
        ElementInterface $element,
        FieldInterface $field,
        array $content,
        int $sourceSiteId,
        int $targetSiteId,
        Job $job,
        TranslationRecord $translationRecord
    ) {
        $this->element = $element;
        $this->field = $field;
        $this->content = $content;
        $this->sourceSiteId = $sourceSiteId;
        $this->targetSiteId = $targetSiteId;
        $this->job = $job;
        $this->translationRecord = $translationRecord;
    }

    /**
     * @return ElementInterface
     */
    public function getElement(): ElementInterface
    {
        return $this->element;
    }

    /**
     * @return self
     */
    public function setElement(ElementInterface $element): self
    {
        $this->element = $element;

        return $this;
    }

    /**
     * @return FieldInterface
     */
    public function getField(): FieldInterface
    {
        return $this->field;
    }

    /**
     * @return array
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @return int
     */
    public function getSourceSiteId(): int
    {
        return $this->sourceSiteId;
    }

    /**
     * @return int
     */
    public function getTargetSiteId(): int
    {
        return $this->targetSiteId;
    }

    /**
     * @return Job
     */
    public function getJob(): Job
    {
        return $this->job;
    }

    /**
     * @return TranslationRecord
     */
    public function getTranslationRecord(): TranslationRecord
    {
        return $this->translationRecord;
    }
}
