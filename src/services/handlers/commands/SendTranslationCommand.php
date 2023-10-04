<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers\commands;

use craft\base\ElementInterface;
use LiltConnectorSDK\Model\JobResponse as LiltJob;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\TranslationRecord;

class SendTranslationCommand
{
    /**
     * @var int
     */
    private $elementId;

    /**
     * @var int
     */
    private $versionId;

    /**
     * @var int
     */
    private $targetSiteId;

    /**
     * @var ElementInterface
     */
    private $element;

    /**
     * @var int
     */
    private $liltJobId;

    /**
     * @var Job
     */
    private $job;

    /**
     * @var TranslationRecord|null
     */
    private $translationRecord;

    /**
     * @param int $elementId
     * @param int $versionId
     * @param int $targetSiteId
     * @param ElementInterface $element
     * @param int $liltJobId
     * @param Job $job
     * @param TranslationRecord|null $translationRecord
     */
    public function __construct(
        int $elementId,
        int $versionId,
        int $targetSiteId,
        ElementInterface $element,
        int $liltJobId,
        Job $job,
        ?TranslationRecord $translationRecord
    ) {
        $this->elementId = $elementId;
        $this->versionId = $versionId;
        $this->targetSiteId = $targetSiteId;
        $this->element = $element;
        $this->liltJobId = $liltJobId;
        $this->job = $job;
        $this->translationRecord = $translationRecord;
    }

    /**
     * @return int
     */
    public function getElementId(): int
    {
        return $this->elementId;
    }

    /**
     * @return int
     */
    public function getVersionId(): int
    {
        return $this->versionId;
    }

    /**
     * @return int
     */
    public function getTargetSiteId(): int
    {
        return $this->targetSiteId;
    }

    /**
     * @return ElementInterface
     */
    public function getElement(): ElementInterface
    {
        return $this->element;
    }

    /**
     * @return int
     */
    public function getLiltJobId(): int
    {
        return $this->liltJobId;
    }

    /**
     * @return Job
     */
    public function getJob(): Job
    {
        return $this->job;
    }

    /**
     * @return TranslationRecord|null
     */
    public function getTranslationRecord(): ?TranslationRecord
    {
        return $this->translationRecord;
    }
}
