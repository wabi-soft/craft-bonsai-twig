<?php

namespace wabisoft\bonsaitwig\utilities;

/**
 * Simplified security utility for basic path sanitization.
 *
 * This class provides essential path cleaning for template paths,
 * focusing only on basic security needs for a dev-only tool.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\utilities
 * @since 6.4.0
 */
class SecurityUtils
{
    /**
     * Template path sanitization with proper path normalization.
     *
     * Uses a stack-based approach to properly handle path traversal attempts,
     * ensuring no ".." or "." segments remain in the final path.
     *
     * @param string $path The path to sanitize
     * @return string The sanitized path
     */
    public static function sanitizeTemplatePath(string $path): string
    {
        if (empty(trim($path))) {
            return '';
        }

        // Normalize backslashes to forward slashes first
        $path = str_replace('\\', '/', $path);

        // Split path into segments
        $segments = explode('/', $path);

        // Use a stack to build the normalized path
        $stack = [];

        foreach ($segments as $segment) {
            // Skip empty segments (from multiple slashes)
            if ($segment === '' || $segment === '.') {
                continue;
            }

            // Handle parent directory traversal
            if ($segment === '..') {
                // Pop from stack if not empty (prevents escaping root)
                if (count($stack) > 0) {
                    array_pop($stack);
                }
                // If stack is empty, ignore the ".." to prevent escaping
                continue;
            }

            // Normal segment - add to stack
            $stack[] = $segment;
        }

        // Join stack with "/" and trim leading/trailing slashes and spaces
        return trim(implode('/', $stack), '/ ');
    }
}
