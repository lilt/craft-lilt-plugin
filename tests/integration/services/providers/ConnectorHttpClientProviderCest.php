<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\services\providers;

use FunctionalTester;
use GuzzleHttp\Client;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use PHPUnit\Framework\Assert;

class ConnectorHttpClientProviderCest extends AbstractIntegrationCest
{
    public function testConnectorVersionValue(FunctionalTester $I): void
    {
        Assert::assertSame(
            sprintf('craft/%s', Craftliltplugin::getInstance()->getVersion()),
            Craftliltplugin::getInstance()->getConnectorVersion()
        );
    }

    public function testProvideClientCarriesConnectorVersionHeader(FunctionalTester $I): void
    {
        $client = Craftliltplugin::getInstance()->connectorHttpClientProvider->provide();

        Assert::assertInstanceOf(Client::class, $client);

        $headers = $client->getConfig('headers');

        Assert::assertArrayHasKey(Craftliltplugin::CONNECTOR_VERSION_HEADER, $headers);
        Assert::assertSame(
            Craftliltplugin::getInstance()->getConnectorVersion(),
            $headers[Craftliltplugin::CONNECTOR_VERSION_HEADER]
        );
    }
}
