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
            'elements/PreviewTranslationsIndex.js',
            'elements/LiltElementIndex.js',
            'elements/LiltBaseElementSelectorModal.js',
            'job-form.js',
            'job-translation-review.js',
            'job-try-again-button.js',
            'job-target-sites.js',
        ];

        $this->css = [
            'job/create.css',
            'job/overview.css',
        ];

        parent::init();
    }
}
