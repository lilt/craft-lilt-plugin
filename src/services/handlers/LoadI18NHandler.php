<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use craft\i18n\I18N;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\i18n\PhpMessageSource;

class LoadI18NHandler
{
    /**
     * @var I18N
     */
    private $i18n;

    public function __construct(I18N $i18n)
    {
        $this->i18n = $i18n;
    }

    public function __invoke(): void
    {
        $this->i18n->translations['lilt'] = [
            'class' => PhpMessageSource::class,
            'sourceLanguage' => Craftliltplugin::getInstance()->sourceLanguage,
            'basePath' => Craftliltplugin::getInstance()->getBasePath() . DIRECTORY_SEPARATOR . 'translations/cache',
            'forceTranslation' => true,
            'allowOverrides' => true,
        ];
    }
}