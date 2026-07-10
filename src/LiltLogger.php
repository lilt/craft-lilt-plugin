<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2025 Lilt Devs
 */

namespace lilthq\craftliltplugin;

use Craft;
use craft\db\Connection;
use Throwable;
use yii\base\NotSupportedException;
use yii\log\Logger;

/**
 * Lilt Logger Utility
 *
 * A static wrapper around Craft's logger that provides a single point for handling
 * both local and remote logging. It automatically enriches log messages with
 * system and environment metadata and sends the logs to Plugin API.
 *
 * @package lilthq\craftliltplugin
 * @since 1.0.0
 */
class LiltLogger
{
    /**
     * Logs a message with the 'trace' level.
     *
     * @param string|array $message The message to be logged.
     * @param string $category The log category.
     */
    public static function debug(string|array $message, string $category = 'application'): void
    {
        static::log($message, Logger::LEVEL_TRACE, $category);
    }

    /**
     * Logs a message with the 'info' level.
     *
     * @param string|array $message The message to be logged.
     * @param string $category The log category.
     */
    public static function info(string|array $message, string $category = 'application'): void
    {
        static::log($message, Logger::LEVEL_INFO, $category);
    }

    /**
     * Logs a message with the 'warning' level.
     *
     * @param string|array $message The message to be logged.
     * @param string $category The log category.
     */
    public static function warning(string|array $message, string $category = 'application'): void
    {
        static::log($message, Logger::LEVEL_WARNING, $category);
    }

    /**
     * Logs a message with the 'error' level.
     *
     * @param string|array $message The message to be logged.
     * @param string $category The log category.
     */
    public static function error(string|array $message, string $category = 'application'): void
    {
        static::log($message, Logger::LEVEL_ERROR, $category);
    }

    /**
     * Logs an exception with rich context.
     *
     * This method automatically extracts key information from the Throwable object,
     * ensuring consistent and detailed error logging.
     *
     * @param Throwable $e The exception to log.
     * @param array $metadata Additional metadata context for the exception.
     * @param string $category The log category.
     */
    public static function logException(Throwable $e, array $metadata = [], string $category = 'application'): void
    {
        $message = array_merge([
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ], $metadata);

        static::log($message, Logger::LEVEL_ERROR, $category);
    }

    /**
     * Gathers general system and environment metadata for logging.
     *
     * @return array The collected system metadata.
     */
    private static function getSystemMetadata(): array
    {
        /** @var Connection $db */
        $db = Craft::$app->getDb();

        try {
            $db_version = $db->getSchema()->getServerVersion();
        } catch (NotSupportedException $ignored) {
            $db_version = null;
        }

        return [
            'craft_version' => Craft::$app->getVersion(),
            'plugin_version' => Craftliltplugin::getInstance()->getVersion(),
            'php_version' => PHP_VERSION,
            'db_driver' => $db->driverName,
            'db_version' => $db_version,
            'environment' => Craft::$app->env,
        ];
    }

    /**
     * The core logging method.
     *
     * This method performs two actions:
     * 1. Logs the message locally using Craft's built-in logger.
     * 2. Sends the message and metadata to the remote Plugin API.
     *
     * Checks to see if `Craftliltplugin::REMOTE_LOG_ERRORS_ONLY == true` to determine if
     * all logs or just error logs should be sent to the Plugin API. If the remote logging
     * fails, that failure is logged locally.
     *
     * @param string|array $message The message to be logged.
     * @param int $level The logging level (e.g., Logger::LEVEL_ERROR).
     * @param string $category The log category.
     * @see Craftliltplugin::REMOTE_LOG_ERRORS_ONLY
     */
    private static function log(string|array $message, int $level, string $category): void
    {
        Craft::getLogger()->log($message, $level, $category);

        if (Craftliltplugin::$REMOTE_LOG_ERRORS_ONLY && $level !== Logger::LEVEL_ERROR) {
            return;
        }

        try {
            $remoteMessage = is_string($message) ? $message : 'Craft CMS Error';
            $metadata = self::getSystemMetadata();

            if (is_array($message)) {
                $metadata['payload'] = $message;
            }

            Craftliltplugin::getInstance()->logsApi->postLog(
                $category,
                $remoteMessage,
                $metadata,
            );
        } catch (Throwable $e) {
            // If the remote API logging fails, log that failure locally.
            Craft::error(
                'Failed to send log to Plugin API. Error: ' . $e->getMessage(),
                __METHOD__
            );
        }
    }
}
