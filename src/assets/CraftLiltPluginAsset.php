<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class CraftLiltPluginAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = '@lilthq/craftliltplugin/assets/resources';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'index.js',
        ];

        $this->css = [
            'index.css',
        ];

        parent::init();
    }
}