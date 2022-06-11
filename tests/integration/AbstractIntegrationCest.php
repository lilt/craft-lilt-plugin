<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration;

use Craft;
use craft\helpers\Db;
use IntegrationTester;
use WireMock\Client\WireMock;
use yii\db\Exception;

class AbstractIntegrationCest
{
    /**
     * @throws Exception
     */
    public function _before(IntegrationTester $I): void {
        WireMock::create('wiremock', 80)->reset();
        Db::truncateTable(Craft::$app->queue->tableName);
    }
    public function _after(IntegrationTester $I): void
    {
        $I->expectAllRequestsAreMatched();
    }
}
