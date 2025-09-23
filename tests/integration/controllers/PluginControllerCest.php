<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers {

    use RuntimeException;
    use yii\web\Response;

    if (!class_exists(TestErrorController::class)) {
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

            public function actionErrorViaEvent(): Response
            {
                return $this->asJson(['success' => true]);
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
    use yii\base\Event;
    use craft\web\Controller;

    class PluginControllerCest extends AbstractIntegrationCest
    {
        public function _before(IntegrationTester $I): void
        {
            parent::_before($I);
            Craft::$app->controllerMap['test-error'] = TestErrorController::class;

            Event::on(
                Controller::class,
                Controller::EVENT_BEFORE_ACTION,
                function($event) {
                    if ($event->sender instanceof TestErrorController && $event->action->id === 'error-via-event') {
                        throw new \RuntimeException('Error from event handler');
                    }
                }
            );
        }

        public function _after(IntegrationTester $I): void
        {
            parent::_after($I);
            unset(Craft::$app->controllerMap['test-error']);

            Event::off(Controller::class, Controller::EVENT_BEFORE_ACTION);
        }

        public function testRunActionCatchesBeforeActionErrorFromChild(IntegrationTester $I): void
        {
            $I->expectLogPostRequest(
                '/api/v1.0/logs',
                'This is a test beforeAction error.',
                200
            );

            $I->sendAjaxPostRequest('index.php?action=test-error/error-in-before-action');

            $I->seeResponseCodeIs(200);
            $responseContent = Craft::$app->getResponse()->content;
            $response = json_decode($responseContent, true);

            Assert::assertIsArray($response);
            Assert::assertFalse($response['success']);
            Assert::assertSame(
                'Unable to resolve the request: test-error/error-in-before-action',
                $response['message']
            );
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

        public function testBeforeActionCatchesInternalFrameworkError(IntegrationTester $I): void
        {
            $I->expectLogPostRequest(
                '/api/v1.0/logs',
                'Error from event handler',
                200
            );

            $I->expectLogPostRequest(
                '/api/v1.0/logs',
                'Unable to resolve the request: test-error/error-via-event',
                200
            );

            $I->sendAjaxPostRequest('index.php?action=test-error/error-via-event');

            $I->seeResponseCodeIs(404);
        }
    }
}
