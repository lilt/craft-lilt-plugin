<?php
/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\elements\actions;

use Craft;
use craft\base\Element;
use craft\base\ElementAction;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use lilthq\craftliltplugin\elements\Job;

//TODO: do we need to set statuses?
class JobSetStatus extends ElementAction
{
    /**
     * @var string|null The status elements should be set to
     */
    public $status;

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Set Status');
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['status'], 'required'];
        $rules[] = [['status'], 'in', 'range' => [array_keys(Job::statuses())]];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        return Craft::$app->getView()->renderTemplate(
            '_components/elementactions/SetStatus/trigger',
            ['statuses']
        );
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        /** @var ElementInterface $elementType */
        $elementType = $this->elementType;
        $elementsService = Craft::$app->getElements();

        $elements = $query->all();
        $failCount = 0;

        foreach ($elements as $element) {
            $element->status = $this->status;

            if ($elementsService->saveElement($element) === false) {
                // Validation error
                $failCount++;
            }
        }
        return true;
    }
}
