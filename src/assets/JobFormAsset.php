<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class JobFormAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@lilthq/craftliltplugin/assets/resources';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'elements/LiltElementIndex.js',
            'job-form.js',
            'job/select-target-sites.js',
            'job/overview.js',
        ];

        $this->css = [
            'job/create.css',
            'job/overview.css',
        ];

        parent::init();
    }
}
