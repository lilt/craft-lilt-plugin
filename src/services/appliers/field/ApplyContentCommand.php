<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use craft\base\ElementInterface;
use craft\base\FieldInterface;

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

    public function __construct(
        ElementInterface $element,
        FieldInterface $field,
        array $content,
        int $sourceSiteId,
        int $targetSiteId
    ) {
        $this->element = $element;
        $this->field = $field;
        $this->content = $content;
        $this->sourceSiteId = $sourceSiteId;
        $this->targetSiteId = $targetSiteId;
    }

    /**
     * @return ElementInterface
     */
    public function getElement(): ElementInterface
    {
        return $this->element;
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
}
