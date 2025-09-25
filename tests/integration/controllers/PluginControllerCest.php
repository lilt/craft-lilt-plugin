<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers {

    use RuntimeException;
    use yii\web\Response;

    if (!class_exists(TestErrorController::class)) {
        class TestErrorController extends PluginController
        {
            protected array|int|bool $allowAnonymous = true;

            public function actionErrorInRunAction(): Response
            {
                throw new RuntimeException('This is a test runAction error.');
            }
        }
    }
}

namespace lilthq\craftliltplugintests\integration\controllers {

    use Craft;
    use IntegrationTester;
    use lilthq\craftliltplugin\controllers\TestErrorController;
    use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;

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
        }

        public function testRunActionLogsAndRethrowsError(IntegrationTester $I): void
        {
            $I->wantTo('verify it logs the error and then re-throws the original exception');

            $I->expectLogsPostRequest('This is a test runAction error.', 200);

            $I->expectThrowable(
                new RuntimeException('This is a test runAction error.'),
                function() use ($I) {
                    $I->sendAjaxPostRequest('index.php?action=test-error/error-in-run-action');
                }
            );
        }

        public function testApiLoggingFailureDoesNotPreventException(IntegrationTester $I): void
        {
            $I->wantTo('verify a remote logging failure doesn\'t prevent the exception');

            $I->expectLogsPostRequest('This is a test runAction error.', 500);

            $I->expectThrowable(
                new RuntimeException('This is a test runAction error.'),
                function() use ($I) {
                    $I->sendAjaxPostRequest('index.php?action=test-error/error-in-run-action');
                }
            );
        }
    }
}
