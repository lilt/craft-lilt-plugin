<?php

/**
 * craft-lilt-plugin plugin for Craft CMS 3.x
 *
 * The Lilt plugin makes it easy for you to send content to Lilt for translation right from within Craft CMS.
 *
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2025 Lilt Devs
 */

namespace Helper;

use yii\log\Logger;

class SpyLogger extends Logger
{
    /**
     * @var array An array of all calls made to the log() method.
     */
    public array $calls = [];

    /**
     * @inheritdoc
     */
    public function log($message, $level, $category = 'application')
    {
        $this->calls[] = compact('message', 'level', 'category');
    }
}
