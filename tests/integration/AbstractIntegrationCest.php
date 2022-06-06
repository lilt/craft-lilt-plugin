<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration;

use IntegrationTester;
use WireMock\Client\WireMock;

class AbstractIntegrationCest
{
    public function _before(IntegrationTester $I): void {
        WireMock::create('wiremock', 80)->reset();
    }
    public function _after(IntegrationTester $I): void
    {
        $I->expectAllRequestsAreMatched();
    }
}
