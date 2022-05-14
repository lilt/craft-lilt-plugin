<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\helpers\Json;

class JobEdit extends ElementAction
{
    /**
     * @var string|null The trigger label
     */
    public $label;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->label === null) {
            $this->label = Craft::t('app', 'Edit');
        }
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return $this->label;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);

        $js = "
(() => {
    new Craft.ElementActionTrigger({
        type: {$type},
        batch: false,
        validateSelection: function(\$selectedItems)
        {
            return Garnish.hasAttr(\$selectedItems.find('.element'), 'data-editable');
        },
        activate: function(\$selectedItems)
        {
            var \$element = \$selectedItems.find('.element:first');
            location.href = Craft.getUrl(\$element.data('url'));
        }
    });
})();
";

        Craft::$app->getView()->registerJs($js);
        return null;
    }
}
