<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2025 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers;

use Craft;
use craft\web\Controller;
use lilthq\craftliltplugin\Craftliltplugin;
use yii\base\Action;

/**
 * Plugin Controller
 *
 * A base controller that extends craft\web\Controller. This provides centralized error
 * handling for all controllers that extend it, reporting any beforeAction and runAction
 * errors that occur back to the Plugin API.
 *
 * It overrides the `beforeAction()` and `runAction()`, wrapping exectution in a `try...catch`
 * block. Any thrown exceptions are caught and processed by `handleError()`, which logs the
 * error locally and remotely in Plugin API.
 *
 * Controllers intended for web or AJAX endpoints wtihin the plugin should extend this to
 * inherit the logging behavior.
 *
 * @package lilthq\craftliltplugin\controllers
 * @since 1.0.0
 */
class PluginController extends Controller
{
    /**
     * Catches any errors that occur before an action runs.
     *
     * This is executed by Yii before any controller action. It wraps the parent method
     * in a `try...catch` to handle exceptions that occur during pre-action events and checks.
     * If an error is caught, it is logged locally and remotely in Plugin API and the action
     * is prevented from running.
     *
     * @param Action $action The action to be executed.
     * @return bool Whether the action should continue to run. Returns `false` on error.
     */

    public function beforeAction($action): bool
    {
        try {
            return parent::beforeAction($action);
        } catch (\Throwable $e) {
            $proofException = new \RuntimeException(
                'PROOF_BEFORE_ACTION_CAUGHT: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
            $this->handleError($proofException, $action->id);

            // prevents the action from running.
            return false;
        }
    }

    /**
     * Catches any errors that occur during an action's execution.
     *
     * This method is responsible for running the specified action. It wraps the parent
     * method in a `try...catch` to handle exceptions that occur within the action itself.
     * If an error is caught, it is logged locally and remotely in Plugin API and a standardized
     * error response is returned.
     *
     * @param string $id The ID of the action to be executed.
     * @param array $params The parameters to be passed to the action.
     * @return mixed The result of the action on success, or a JSON Response on failure.
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
     * Handles and logs errors thrown caught from `beforeAction` and `runAction`.
     *
     * This method implements both local logging via `Craft::error` and remote logging
     * to the Plugin API's POST /logs endpoint.
     *
     * If the Plugin API call fails, the failure will also be logged locally to ensure
     * no error context is lost.
     *
     * @param \Throwable $e The thrown exception that occurred.
     * @param string $event The ID of the action/event where the error occurred.
     * @param array $params The parameters that were passed to the action.
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
        } catch (\Throwable $err) {
            // If the remote API logging fails, log that failure locally.
            Craft::error('Controller error: ' . json_encode([
                'event' => $event,
                'message' => $err->getMessage(),
                'route' => $this->id . '/' . $event,
                'method' => $request->getMethod(),
            ]), __METHOD__);
        }
    }
}
