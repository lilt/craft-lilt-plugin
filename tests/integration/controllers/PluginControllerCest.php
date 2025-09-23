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

// This is the actual test suite namespace.
namespace lilthq\craftliltplugintests\integration\controllers {

    use Craft;
    use Exception;
    use IntegrationTester;
    use lilthq\craftliltplugin\controllers\TestErrorController;
    use lilthq\craftliltplugin\Craftliltplugin;
    use lilthq\craftliltplugin\services\LogsApi;
    use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
    use PHPUnit\Framework\MockObject\MockObject;

    class PluginControllerCest extends AbstractIntegrationCest
    {
        public function _before(IntegrationTester $I): void
        {
            parent::_before($I);
            Craft::$app->controllerMap['test-error'] = TestErrorController::class;
        }

        public function _after(IntegrationTester $I): void
        {
            parent::_after($I);
            unset(Craft::$app->controllerMap['test-error']);
            Craftliltplugin::setInstance(null);
            Craft::$app->set('craftliltplugin', null);
        }

        public function testRunActionCatchesError(IntegrationTester $I): void
        {
            /** @var MockObject|LogsApi $logsApiMock */
            $logsApiMock = $this->createMock(LogsApi::class);
            $logsApiMock->expects($I->once())->method('postLog');
            $this->mockPluginService($logsApiMock);

            $I->sendAjaxPostRequest('index.php?action=test-error/error-in-run-action');

            $I->seeResponseCodeIs(200);
            $I->seeResponseIsJson();
            $I->seeResponseContainsJson([
                'success' => false,
                'message' => 'This is a test runAction error.',
            ]);
        }

        public function testBeforeActionCatchesError(IntegrationTester $I): void
        {
            /** @var MockObject|LogsApi $logsApiMock */
            $logsApiMock = $this->createMock(LogsApi::class);
            $logsApiMock->expects($I->once())
                ->method('postLog')
                ->with(
                    $I->equalTo('error-in-before-action'),
                    $I->equalTo('This is a test beforeAction error.')
                );
            $this->mockPluginService($logsApiMock);

            $I->sendAjaxPostRequest('index.php?action=test-error/error-in-before-action');

            $I->seeResponseCodeIs(404);
        }

        public function testApiLoggingFailureDoesNotAffectUserResponse(IntegrationTester $I): void
        {
            /** @var MockObject|LogsApi $logsApiMock */
            $logsApiMock = $this->createMock(LogsApi::class);
            $logsApiMock->expects($I->once())
                ->method('postLog')
                ->willThrowException(new Exception('Remote API is down.'));
            $this->mockPluginService($logsApiMock);

            $I->sendAjaxPostRequest('index.php?action=test-error/error-in-run-action');

            $I->seeResponseCodeIs(200);
            $I->seeResponseContainsJson(['message' => 'This is a test runAction error.']);
        }

        private function mockPluginService(LogsApi|MockObject $logsApiMock): void
        {
            $pluginMock = $this->createMock(Craftliltplugin::class);
            $pluginMock->logsApi = $logsApiMock;
            Craftliltplugin::setInstance($pluginMock);
        }
    }
}
