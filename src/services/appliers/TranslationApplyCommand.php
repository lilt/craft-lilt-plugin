<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers;

use craft\base\ElementInterface;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;

class TranslationApplyCommand
{
    /**
     * @var ElementInterface
     */
    private $element;

    /**
     * @var Job
     */
    private $job;

    /**
     * @var array
     */
    private $content;

    /**
     * @var string
     */
    private $targetLanguage;

    /**
     * @param ElementInterface $element
     * @param Job $job
     * @param array $content
     * @param string $targetLanguage
     */
    public function __construct(ElementInterface $element, Job $job, array $content, string $targetLanguage)
    {
        $this->element = $element;
        $this->job = $job;
        $this->content = $content;
        $this->targetLanguage = $targetLanguage;
    }

    /**
     * @return ElementInterface
     */
    public function getElement(): ElementInterface
    {
        return $this->element;
    }

    /**
     * @return Job
     */
    public function getJob(): Job
    {
        return $this->job;
    }

    /**
     * @return array
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getTargetLanguage(): string
    {
        return $this->targetLanguage;
    }

    public function getTargetSiteId(): int
    {
        return Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage($this->targetLanguage);
    }

    public function getSourceSiteId(): int
    {
        return $this->job->sourceSiteId;
    }
}