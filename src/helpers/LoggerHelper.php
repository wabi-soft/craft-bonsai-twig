<?php

namespace wabisoft\bonsaitwig\helpers;

use Craft;
use Psr\Log\LogLevel;

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
     * @param string $level The log level (use LogLevel constants)
     * @param string $message The message to log
     */
    public static function log($level, $message): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1] ?? [];
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 0;

        $message = sprintf(
            "[BonsaiTwig] [%s:%d] %s",
            basename($file),
            $line,
            $message
        );

        Craft::getLogger()->log($message, $level, 'bonsai-twig');
    }

    /**
     * Logs an informational message to our custom log target only in dev mode.
     *
     * @param string $message The message to log
     */
    public static function info($message): void
    {
        if (Craft::$app->config->general->devMode) {
            self::log(LogLevel::INFO, $message);
        }
    }

    /**
     * Logs an error message to our custom log target.
     *
     * @param string $message The message to log
     */
    public static function error($message): void
    {
        self::log(LogLevel::ERROR, $message);
    }

    /**
     * Logs a warning message to our custom log target.
     *
     * @param string $message The message to log
     */
    public static function warning($message): void
    {
        self::log(LogLevel::WARNING, $message);
    }

    /**
     * Logs a debug message to our custom log target only in dev mode.
     *
     * Useful for verbose debugging information.
     *
     * @param string $message The message to log
     */
    public static function debug($message): void
    {
        if (Craft::$app->config->general->devMode) {
            self::log(LogLevel::DEBUG, $message);
        }
    }
}
