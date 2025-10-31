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
