<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\utilities;

use Craft;
use Helper\SpyLogger;
use IntegrationTester;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\LiltLogger;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use PHPUnit\Framework\Assert;
use ReflectionClass;
use RuntimeException;

class LiltLoggerCest extends AbstractIntegrationCest
{
    private ?SpyLogger $spyLogger = null;

    public function _before(IntegrationTester $I): void
    {
        parent::_before($I);

        $I->backupService('logger');

        $this->spyLogger = new SpyLogger();
        Craft::$app->set('logger', $this->spyLogger);
    }

    public function _after(IntegrationTester $I): void
    {
        $I->restoreOriginalServices();
        parent::_after($I);
    }

    private function setRemoteLogErrorsOnly(bool $value): void
    {
        $reflection = new ReflectionClass(Craftliltplugin::class);
        $reflection->setStaticPropertyValue('REMOTE_LOG_ERRORS_ONLY', $value);
    }

    public function testNonErrorLogsAreOnlyLocalWhenFlagIsTrue(IntegrationTester $I): void
    {
        $I->wantTo('verify that info/debug/warning logs are only local when REMOTE_LOG_ERRORS_ONLY is true');
        $this->setRemoteLogErrorsOnly(true);

        LiltLogger::info('Test info message');
        LiltLogger::warning('Test warning message');

        Assert::assertCount(2, $this->spyLogger->calls);
    }

    public function testErrorLogsAreSentWhenFlagIsTrue(IntegrationTester $I): void
    {
        $I->wantTo('verify that error logs are sent remotely when REMOTE_LOG_ERRORS_ONLY is true');
        $this->setRemoteLogErrorsOnly(true);

        $I->expectLogsPostRequest('Test error message', 200);
        $I->expectLogsPostRequest('Test exception', 200);

        LiltLogger::error('Test error message');
        LiltLogger::logException(new RuntimeException('Test exception'));

        Craftliltplugin::getInstance()->logsApi->flush();

        Assert::assertCount(2, $this->spyLogger->calls);
    }

    public function testAllLogsAreSentWhenFlagIsFalse(IntegrationTester $I): void
    {
        $I->wantTo('verify that all log levels are sent remotely when REMOTE_LOG_ERRORS_ONLY is false');
        $this->setRemoteLogErrorsOnly(false);

        $I->expectLogsPostRequest('Test info message', 200);
        $I->expectLogsPostRequest('Test exception', 200);

        LiltLogger::info('Test info message');
        LiltLogger::logException(new RuntimeException('Test exception'));

        Craftliltplugin::getInstance()->logsApi->flush();

        Assert::assertCount(2, $this->spyLogger->calls);
    }
}
