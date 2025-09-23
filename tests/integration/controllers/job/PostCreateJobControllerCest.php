<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers {

    use RuntimeException;
    use yii\web\Response;

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

        public function testRunActionCatchesError(IntegrationTester $I): void
        {
            $I->expectLogPostRequest(
                '/api/v1.0/logs',
                'This is a test runAction error.',
                200
            );

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
            $I->expectLogPostRequest(
                '/api/v1.0/logs',
                'This is a test beforeAction error.',
                200
            );

            $I->sendAjaxPostRequest('index.php?action=test-error/error-in-before-action');

            $I->seeResponseCodeIs(404);
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
            $I->seeResponseContainsJson(['message' => 'This is a test runAction error.']);
        }
    }
}

