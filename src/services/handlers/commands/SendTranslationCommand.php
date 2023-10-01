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
     * @var LiltJob
     */
    private $liltJob;

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
     * @param LiltJob $liltJob
     * @param Job $job
     * @param TranslationRecord|null $translationRecord
     */
    public function __construct(
        int $elementId,
        int $versionId,
        int $targetSiteId,
        ElementInterface $element,
        LiltJob $liltJob,
        Job $job,
        ?TranslationRecord $translationRecord
    ) {
        $this->elementId = $elementId;
        $this->versionId = $versionId;
        $this->targetSiteId = $targetSiteId;
        $this->element = $element;
        $this->liltJob = $liltJob;
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
     * @return LiltJob
     */
    public function getLiltJob(): LiltJob
    {
        return $this->liltJob;
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
