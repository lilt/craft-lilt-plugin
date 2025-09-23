<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers {
    use yii\web\Response;
    use RuntimeException;

    class TestErrorController extends PluginController
    {
        protected array|int|bool $allowAnonymous = true;

        public function beforeAction($action): bool
        {
            if ($action->id === 'error-in-before-action') {
                throw new RuntimeException('This is a test beforeAction error.');
            }
            return parent::beforeAction($action);
        }

        public function actionErrorInRunAction(): Response
        {
            throw new RuntimeException('This is a test runAction error.');
        }
    }
}

namespace lilthq\craftliltplugintests\integration\controllers {

    use Craft;
    use Exception;
    use IntegrationTester;
    use lilthq\craftliltplugin\controllers\TestErrorController;
    use lilthq\craftliltplugin\Craftliltplugin;
    use lilthq\craftliltplugin\services\LogsApi;
    use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
    use PHPUnit\Framework\MockObject\MockObject;
    use yii\log\FileTarget;
    use yii\log\Logger;

    class PluginControllerCest extends AbstractIntegrationCest
    {
        private ?Logger $originalLogger;
        private ?string $testLogPath;

        public function _before(IntegrationTester $I): void
        {
            parent::_before($I);

            Craft::$app->controllerMap['test-error'] = TestErrorController::class;

            $this->originalLogger = Craft::getLogger();
            $this->testLogPath = Craft::getAlias('@storage/logs/test-web.log');
            if (file_exists($this->testLogPath)) {
                unlink($this->testLogPath);
            }

            $testLogger = new Logger();
            $testLogger->targets['test-file'] = new FileTarget([
                'logFile' => $this->testLogPath,
                'levels' => ['error', 'warning', 'info', 'trace'],
                'logVars' => [],
            ]);

            Craft::setLogger($testLogger);
        }

        public function _after(IntegrationTester $I): void
        {
            parent::_after($I);

            unset(Craft::$app->controllerMap['test-error']);

            if ($this->originalLogger) {
                Craft::setLogger($this->originalLogger);
            }

            if ($this->testLogPath && file_exists($this->testLogPath)) {
                unlink($this->testLogPath);
            }

            Craftliltplugin::setInstance(null);
            Craft::$app->set('craftliltplugin', null);
        }

        public function testRunActionCatchesAndLogsError(IntegrationTester $I): void
        {
            /** @var MockObject|LogsApi $logsApiMock */
            $logsApiMock = $I->make(LogsApi::class);
            $logsApiMock->expects($I->once())->method('postLog');
            $this->mockPluginService($logsApiMock);

            $I->sendAjaxPostRequest('index.php?action=test-error/error-in-run-action');

            $I->seeResponseCodeIs(200);
            $I->seeResponseIsJson();
            $I->seeResponseContainsJson([
                'success' => false,
                'message' => 'This is a test runAction error.',
            ]);

            $I->openFile($this->testLogPath);
            $I->seeInThisFile('Controller error:');
            $I->seeInThisFile('"event":"error-in-run-action"');
            $I->seeInThisFile('"message":"This is a test runAction error."');
        }

        public function testBeforeActionCatchesAndLogsError(IntegrationTester $I): void
        {
            /** @var MockObject|LogsApi $logsApiMock */
            $logsApiMock = $I->make(LogsApi::class);
            $logsApiMock->expects($I->once())
                ->method('postLog')
                ->with(
                    $I->equalTo('error-in-before-action'),
                    $I->equalTo('This is a test beforeAction error.')
                );
            $this->mockPluginService($logsApiMock);

            $I->sendAjaxPostRequest('index.php?action=test-error/error-in-before-action');

            $I->seeResponseCodeIs(404);

            $I->openFile($this->testLogPath);
            $I->seeInThisFile('Controller error:');
            $I->seeInThisFile('"event":"error-in-before-action"');
            $I->seeInThisFile('"message":"This is a test beforeAction error."');
        }

        public function testHandleErrorLogsApiFailureFailsafe(IntegrationTester $I): void
        {
            /** @var MockObject|LogsApi $logsApiMock */
            $logsApiMock = $I->make(LogsApi::class);
            $logsApiMock->expects($I->once())
                ->method('postLog')
                ->willThrowException(new Exception('Remote API is down.'));
            $this->mockPluginService($logsApiMock);

            $I->sendAjaxPostRequest('index.php?action=test-error/error-in-run-action');

            $I->seeResponseCodeIs(200);
            $I->seeResponseContainsJson(['message' => 'This is a test runAction error.']);

            $I->openFile($this->testLogPath);
            $I->seeInThisFile('Controller error:');
            $I->seeInThisFile('"message":"This is a test runAction error."');
            $I->seeInThisFile('Failed to send log to API. Reason:');
            $I->seeInThisFile('"message":"Remote API is down."');
        }

        private function mockPluginService(LogsApi|MockObject $logsApiMock): void
        {
            $pluginMock = $this->createMock(Craftliltplugin::class);
            $pluginMock->logsApi = $logsApiMock;
            Craftliltplugin::setInstance($pluginMock);
        }
    }
}
