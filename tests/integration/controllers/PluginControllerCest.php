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
    use PHPUnit\Framework\Assert;

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

        public function testRunActionCatchesError(IntegrationTester $I): void
        {
            $I->expectLogPostRequest(
                '/api/v1.0/logs',
                'This is a test runAction error.',
                200
            );

            $I->sendAjaxPostRequest('index.php?action=test-error/error-in-run-action');
            $I->seeResponseCodeIs(200);

            $responseContent = Craft::$app->getResponse()->content;
            $response = json_decode($responseContent, true);

            Assert::assertIsArray($response);
            Assert::assertFalse($response['success']);
            Assert::assertSame('This is a test runAction error.', $response['message']);
        }
        public function testApiLoggingFailureDoesNotAffectUserResponse(IntegrationTester $I): void
        {
            $I->expectLogPostRequest(
                '/api/v1.0/logs',
                'This is a test runAction error.',
                500
            );

            $I->sendAjaxPostRequest('index.php?action=test-error/error-in-run-action');
            $I->seeResponseCodeIs(200);

            $responseContent = Craft::$app->getResponse()->content;
            $response = json_decode($responseContent, true);
            Assert::assertSame('This is a test runAction error.', $response['message']);
        }
    }
}
