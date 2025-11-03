<?php

namespace wabisoft\bonsaitwig\helpers;

use Craft;
use yii\log\Logger;

/**
 * Custom logger helper for BonsaiTwig plugin.
 *
 * Provides simplified logging methods that write to a dedicated log file
 * for easier debugging and monitoring.
 *
 * @author Wabisoft
 * @since 7.0.11
 */
class LoggerHelper
{
    /**
     * Logs a message to our custom log target.
     *
     * @param int $level The log level (use Logger::LEVEL_* constants)
     * @param string $message The message to log
     */
    public static function log(int $level, string $message): void
    {
        // Get full backtrace to walk through wrapper methods
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        // Find the first frame outside of this LoggerHelper class
        $file = 'unknown';
        $line = 0;

        foreach ($trace as $frame) {
            // Skip frames that are in this LoggerHelper class
            if (isset($frame['class']) && $frame['class'] === __CLASS__) {
                continue;
            }

            // Skip frames that are in this file (for static calls without class)
            if (isset($frame['file']) && $frame['file'] === __FILE__) {
                continue;
            }

            // Found the first frame outside LoggerHelper
            if (isset($frame['file'])) {
                $file = $frame['file'];
                $line = $frame['line'] ?? 0;
                break;
            }
        }

        $formattedMessage = sprintf(
            "[BonsaiTwig] [%s:%d] %s",
            basename($file),
            $line,
            $message
        );

        Craft::getLogger()->log($formattedMessage, $level, 'bonsai-twig');
    }

    /**
     * Logs an informational message to our custom log target only in dev mode.
     *
     * @param string $message The message to log
     */
    public static function info(string $message): void
    {
        if (Craft::$app->config->general->devMode) {
            self::log(Logger::LEVEL_INFO, $message);
        }
    }

    /**
     * Logs an error message to our custom log target.
     *
     * @param string $message The message to log
     */
    public static function error(string $message): void
    {
        self::log(Logger::LEVEL_ERROR, $message);
    }

    /**
     * Logs a warning message to our custom log target.
     *
     * @param string $message The message to log
     */
    public static function warning(string $message): void
    {
        self::log(Logger::LEVEL_WARNING, $message);
    }

    /**
     * Logs a debug message to our custom log target only in dev mode.
     *
     * Useful for verbose debugging information.
     *
     * @param string $message The message to log
     */
    public static function debug(string $message): void
    {
        if (Craft::$app->config->general->devMode) {
            self::log(Logger::LEVEL_TRACE, $message);
        }
    }
}
