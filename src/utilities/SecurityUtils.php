<?php

namespace wabisoft\bonsaitwig\utilities;

use craft\helpers\StringHelper;
use wabisoft\bonsaitwig\exceptions\InvalidTemplatePathException;

/**
 * Security utility class for path sanitization and validation.
 *
 * This class provides comprehensive security utilities for template path handling,
 * input validation, and protection against common security vulnerabilities like
 * path traversal attacks.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\utilities
 * @since 6.4.0
 */
class SecurityUtils
{
    /**
     * Maximum allowed path length to prevent buffer overflow attacks.
     */
    private const MAX_PATH_LENGTH = 1024;

    /**
     * Allowed characters in template paths (alphanumeric, dash, underscore, slash, dot).
     */
    private const ALLOWED_PATH_CHARS = '/^[a-zA-Z0-9\-_\/\.]+$/';

    /**
     * Dangerous path patterns that should be blocked.
     */
    private const DANGEROUS_PATTERNS = [
        '../',
        '..\\',
        './',
        '.\\',
        '~/',
        '~\\',
    ];

    /**
     * Sanitizes a template path to prevent security vulnerabilities.
     *
     * This method performs comprehensive path sanitization including:
     * - Path traversal attack prevention
     * - Absolute path blocking
     * - Character validation
     * - Length validation
     * - Normalization of path separators
     *
     * @param string $path The template path to sanitize
     * @return string The sanitized template path
     * @throws InvalidTemplatePathException If the path contains dangerous patterns or is invalid
     */
    public static function sanitizeTemplatePath(string $path): string
    {
        // Check for empty path
        if (empty(trim($path))) {
            throw new InvalidTemplatePathException(
                path: $path,
                reason: 'Template path cannot be empty'
            );
        }

        // Check path length
        if (strlen($path) > self::MAX_PATH_LENGTH) {
            throw new InvalidTemplatePathException(
                path: $path,
                reason: sprintf('Template path exceeds maximum length of %d characters', self::MAX_PATH_LENGTH)
            );
        }

        // Check for dangerous patterns
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (str_contains($path, $pattern)) {
                throw new InvalidTemplatePathException(
                    path: $path,
                    reason: sprintf('Path contains dangerous pattern: %s', $pattern)
                );
            }
        }

        // Check for absolute paths (security risk)
        if (self::isAbsolutePath($path)) {
            throw new InvalidTemplatePathException(
                path: $path,
                reason: 'Absolute paths are not allowed in template paths'
            );
        }

        // Normalize path separators to forward slashes early
        $normalized = str_replace('\\', '/', $path);
        $normalized = trim($normalized);

        // Normalize each path segment by transliterating and replacing invalid characters
        $segments = array_filter(explode('/', $normalized), static fn($s) => $s !== '');
        $normalizedSegments = [];
        foreach ($segments as $seg) {
            $normalizedSegments[] = self::normalizePathSegment($seg);
        }
        $sanitizedPath = implode('/', $normalizedSegments);

        // Now validate allowed characters on the sanitized path
        if ($sanitizedPath === '' || !preg_match(self::ALLOWED_PATH_CHARS, $sanitizedPath)) {
            throw new InvalidTemplatePathException(
                path: $path,
                reason: 'Template path contains invalid characters'
            );
        }

        // Remove leading and trailing slashes/spaces just in case
        $sanitizedPath = trim($sanitizedPath, '/ ');

        // Remove double slashes
        $sanitizedPath = preg_replace('/\/+/', '/', $sanitizedPath);

        // Final validation - ensure path is not empty after sanitization
        if (empty($sanitizedPath)) {
            throw new InvalidTemplatePathException(
                path: $path,
                reason: 'Path is empty after sanitization'
            );
        }

        return $sanitizedPath;
    }

    /**
     * Validates that a template path is safe for file system access.
     *
     * Performs additional validation beyond basic sanitization to ensure
     * the path is safe for actual file system operations.
     *
     * @param string $path The template path to validate
     * @return bool True if the path is valid and safe
     * @throws InvalidTemplatePathException If the path is invalid or unsafe
     */
    public static function validateTemplatePath(string $path): bool
    {
        // First sanitize the path (this will throw if invalid)
        $sanitizedPath = self::sanitizeTemplatePath($path);

        // Check for null bytes (can cause issues in some systems)
        if (str_contains($sanitizedPath, "\0")) {
            throw new InvalidTemplatePathException(
                path: $path,
                reason: 'Path contains null bytes'
            );
        }

        // Check for consecutive dots (potential traversal)
        if (str_contains($sanitizedPath, '..')) {
            throw new InvalidTemplatePathException(
                path: $path,
                reason: 'Path contains consecutive dots'
            );
        }

        // Ensure path doesn't start with a dot (hidden files)
        if (str_starts_with($sanitizedPath, '.')) {
            throw new InvalidTemplatePathException(
                path: $path,
                reason: 'Path cannot start with a dot'
            );
        }

        // Check for reserved names (Windows specific but good practice)
        $pathParts = explode('/', $sanitizedPath);
        foreach ($pathParts as $part) {
            if (self::isReservedName($part)) {
                throw new InvalidTemplatePathException(
                    path: $path,
                    reason: sprintf('Path contains reserved name: %s', $part)
                );
            }
        }

        return true;
    }

    /**
     * Sanitizes an array of template paths.
     *
     * @param array<string> $paths Array of template paths to sanitize
     * @return array<string> Array of sanitized template paths
     * @throws InvalidTemplatePathException If any path is invalid
     */
    public static function sanitizeTemplatePaths(array $paths): array
    {
        $sanitizedPaths = [];
        
        foreach ($paths as $path) {
            if (!is_string($path)) {
                throw new InvalidTemplatePathException(
                    path: (string) $path,
                    reason: 'Template path must be a string'
                );
            }
            
            $sanitizedPaths[] = self::sanitizeTemplatePath($path);
        }

        return $sanitizedPaths;
    }

    /**
     * Checks if a path is an absolute path.
     *
     * @param string $path The path to check
     * @return bool True if the path is absolute
     */
    private static function isAbsolutePath(string $path): bool
    {
        // Unix/Linux absolute paths start with /
        if (str_starts_with($path, '/')) {
            return true;
        }

        // Windows absolute paths (C:, D:, etc.)
        if (PHP_OS_FAMILY === 'Windows' && preg_match('/^[A-Za-z]:/', $path)) {
            return true;
        }

        // UNC paths (\\server\share)
        if (str_starts_with($path, '\\\\')) {
            return true;
        }

        return false;
    }

    /**
     * Checks if a filename is a reserved system name.
     *
     * @param string $name The filename to check
     * @return bool True if the name is reserved
     */
    private static function isReservedName(string $name): bool
    {
        // Windows reserved names
        $reservedNames = [
            'CON', 'PRN', 'AUX', 'NUL',
            'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9',
            'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'
        ];

        $upperName = strtoupper($name);
        
        // Check exact matches
        if (in_array($upperName, $reservedNames, true)) {
            return true;
        }

        // Check with extensions (e.g., CON.txt)
        $nameWithoutExt = pathinfo($upperName, PATHINFO_FILENAME);
        return in_array($nameWithoutExt, $reservedNames, true);
    }

    /**
     * Normalize a single path segment by transliterating to ASCII and replacing invalid characters.
     */
    private static function normalizePathSegment(string $segment): string
    {
        $segment = self::transliterateToAscii($segment);
        // Replace any remaining invalid characters with a dash
        $segment = preg_replace('/[^A-Za-z0-9\._-]+/', '-', $segment ?? '');
        // Collapse multiple dashes
        $segment = preg_replace('/-+/', '-', $segment ?? '-');
        // Trim spaces, dashes
        $segment = trim((string)$segment, " -");
        // Avoid leading dots which would create hidden paths
        $segment = ltrim($segment, '.');
        if ($segment === '') {
            $segment = '-';
        }
        return $segment;
    }

    /**
     * Best-effort transliteration of a string to ASCII.
     */
    private static function transliterateToAscii(string $value): string
    {
        // Prefer Craft's built-in ASCII transliteration
        try {
            $ascii = StringHelper::toAscii($value);
            if ($ascii !== '') {
                return $ascii;
            }
        } catch (\Throwable $e) {
            // fall through to other strategies
        }
        // Fallback to intl transliterator if available
        if (function_exists('transliterator_transliterate')) {
            $result = transliterator_transliterate('Any-Latin; Latin-ASCII', $value);
            if ($result !== null) {
                return $result;
            }
        }
        // Final fallback to iconv
        $result = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($result !== false) {
            return (string)$result;
        }
        return $value;
    }

    /**
     * Generates a secure cache key from template information.
     *
     * Creates a secure hash-based cache key that prevents cache poisoning
     * and ensures consistent key generation.
     *
     * @param array<string> $templates Array of template paths
     * @param string $type Template type identifier
     * @param array<string, mixed> $context Additional context for key generation
     * @return string Secure cache key
     */
    public static function generateSecureCacheKey(array $templates, string $type, array $context = []): string
    {
        // Sanitize all template paths first
        $sanitizedTemplates = self::sanitizeTemplatePaths($templates);
        
        // Create a consistent data structure for hashing
        $keyData = [
            'templates' => $sanitizedTemplates,
            'type' => $type,
            'context' => $context,
            'version' => '1.0' // Version for cache invalidation if needed
        ];

        // Generate secure hash
        $serializedData = serialize($keyData);
        return 'bonsai_twig:' . hash('sha256', $serializedData);
    }

    /**
     * Validates file permissions for template access.
     *
     * Checks if the current process has appropriate permissions to read
     * the specified template file.
     *
     * @param string $templatePath The template path to check
     * @return bool True if the file is readable
     */
    public static function validateFilePermissions(string $templatePath): bool
    {
        // First validate the path itself
        self::validateTemplatePath($templatePath);

        // Check if file exists and is readable
        if (!file_exists($templatePath)) {
            return false;
        }

        if (!is_readable($templatePath)) {
            return false;
        }

        // Ensure it's a regular file (not a directory or special file)
        if (!is_file($templatePath)) {
            return false;
        }

        return true;
    }
}