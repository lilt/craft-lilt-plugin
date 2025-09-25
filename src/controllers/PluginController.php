<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2025 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers;

use craft\web\Controller;
use lilthq\craftliltplugin\LiltLogger;
use Throwable;

/**
 * Plugin Controller
 *
 * A base controller that extends craft\web\Controller. This provides centralized error
 * handling for all controllers that extend it, reporting any runAction errors that occur
 * back to the Plugin API.
 *
 * It runAction()`, wrapping the execution in a `try...catch` block. Any thrown exceptions
 * are caught and processed by `handleError()`, which logs the error locally and remotely
 * in Plugin API.
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
     * @throws Throwable The original exception caught.
     */
    public function runAction($id, $params = []): mixed
    {
        try {
            return parent::runAction($id, $params);
        } catch (Throwable $e) {
            LiltLogger::logException($e, [
                'event' => $id,
                'route' => $this->getRoute(),
                'params' => $params,
            ]);

            throw $e;
        }
    }
}
