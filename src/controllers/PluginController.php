<?php

namespace lilthq\craftliltplugin\controllers;

use Craft;
use craft\web\Controller;
use lilthq\craftliltplugin\Craftliltplugin;
use yii\base\Action;

class PluginController extends Controller
{
    /**
     * Catch any errors that occur before an action runs within a controller. If an error
     * is caught, a log will be sent to Plugin API's POST /logs endpoint.
     *
     * @param Action $action the action to be executed.
     * @return bool whether the action should continue to run or not.
     */
    public function beforeAction($action): bool
    {
        try {
            return parent::beforeAction($action);
        } catch (\Throwable $e) {
            $this->handleError($e, $action->id);

            return false;
        }
    }

    /**
     * Catch any errors that occur during an action being executed within a controller.
     * If an error occurs, a log will be sent to Plugin API's POST /logs endpoint.
     *
     * @param string $id the ID of the action being executed.
     * @param array $params the parameters passed to the action.
     * @return mixed the result of the action.
     */
    public function runAction($id, $params = []): mixed
    {
        try {
            return parent::runAction($id, $params);
        } catch (\Throwable $e) {
            $this->handleError($e, $id, $params);

            return $this->asJson([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handles errors that occur within beforeAction or runAction. This will trigger a
     * Craft::error for server logs as well as send the log to Plugin API's POST /logs
     * endpoint.
     *
     * @param \Throwable $e the thrown exception that occurred.
     * @param string $event the ID of the event.
     * @param array $params the parameters passed to the action.
     */
    private function handleError(\Throwable $e, string $event, array $params = []): void
    {
        $request = Craft::$app->getRequest();

        Craft::error('Controller error: ' . json_encode([
            'event' => $event,
            'message' => $e->getMessage(),
            'route' => $this->id . '/' . $event,
            'method' => $request->getMethod(),
        ]), __METHOD__);

        try {
            $metadata = [
                'event' => $request->getMethod(),
                'message' => $e->getMessage(),
                'timestamp' => time(),
                'body' => $request->getBodyParams() ?: $request->getQueryParams(),
                'params' => $params,
            ];

            Craftliltplugin::getInstance()->logsApi->postLog(
                $event,
                $e->getMessage(),
                $metadata,
            );
        } catch (\Throwable $ignored) {}
    }
}
