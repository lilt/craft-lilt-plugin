<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class JobsAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@lilthq/craftliltplugin/assets/resources';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'job-sync-button.js',
        ];

        $this->css = [];

        parent::init();
    }
}
