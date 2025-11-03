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
     * Basic template path sanitization.
     *
     * @param string $path The path to sanitize
     * @return string The sanitized path
     */
    public static function sanitizeTemplatePath(string $path): string
    {
        if (empty(trim($path))) {
            return '';
        }

        // Remove path traversal attempts
        $path = str_replace(['../', '..\\'], '', $path);
        
        // Normalize separators
        $path = str_replace('\\', '/', $path);
        
        // Clean up multiple slashes
        $path = preg_replace('/\/+/', '/', $path);
        
        // Trim and return
        return trim($path, '/ ');
    }
}
