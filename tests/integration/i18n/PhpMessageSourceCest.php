<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\i18n;

use Craft;
use IntegrationTester;
use lilthq\craftliltplugin\Craftliltplugin;
use PHPUnit\Framework\Assert;

class PhpMessageSourceCest
{
    public function testTranslationsLoading(IntegrationTester $I): void
    {
        Craftliltplugin::getInstance()->i18NRepository
            ->new(
                Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
                Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('de-DE'),
                'This is test message',
                'Dies ist eine Testnachricht'
            )
            ->save();

        Craftliltplugin::getInstance()->i18NRepository
            ->new(
                Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
                Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('ru-RU'),
                'This is test message',
                'Это тестовое сообщение'
            )
            ->save();

        Craftliltplugin::getInstance()->i18NRepository
            ->new(
                Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('en-US'),
                Craftliltplugin::getInstance()->languageMapper->getSiteIdByLanguage('es-ES'),
                'This is test message',
                'Este es un mensaje de prueba'
            )
            ->save();

        $liltMessageSource = Craft::$app->i18n->getMessageSource('lilt');

        $actualDeTranslation = $liltMessageSource->translate('lilt', 'This is test message', 'de-DE');
        $actualRuTranslation = $liltMessageSource->translate('lilt', 'This is test message', 'ru-RU');
        $actualEsTranslation = $liltMessageSource->translate('lilt', 'This is test message', 'es-ES');

        $actualFrTranslation = $liltMessageSource->translate('lilt', 'This is test message', 'fr-FR');

        Assert::assertSame('Dies ist eine Testnachricht', $actualDeTranslation);
        Assert::assertSame('Это тестовое сообщение', $actualRuTranslation);
        Assert::assertSame('Este es un mensaje de prueba', $actualEsTranslation);

        Assert::assertFalse($actualFrTranslation);
    }
}