<?php

namespace wabisoft\bonsaitwig\services;

use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use wabisoft\bonsaitwig\enums\DebugMode;
use wabisoft\bonsaitwig\enums\TemplateType;
use wabisoft\bonsaitwig\exceptions\InvalidTemplatePathException;
use wabisoft\bonsaitwig\exceptions\TemplateNotFoundException;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;

/**
 * HierarchyTemplateLoader Component
 *
 * This component handles the core template loading and rendering logic based on hierarchical
 * template resolution. It supports development mode features like debug information display,
 * template path visualization, and performance monitoring.
 *
 * The loader implements intelligent caching, template existence checking, and comprehensive
 * error handling for production and development environments.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\services
 * @since 6.4.0
 */
class HierarchyTemplateLoader extends Component
{
    /**
     * Loads and renders a template from a hierarchical list of possible templates.
     *
     * This is the core method that handles template resolution, caching, and rendering.
     * It iterates through the provided template paths until it finds an existing template,
     * then renders it with the provided variables. In development mode, it can display
     * debug information about the template resolution process.
     *
     * Features:
     * - Hierarchical template resolution with fallback patterns
     * - Intelligent caching for production performance
     * - Development mode debug information display
     * - Comprehensive error handling and logging
     * - Template existence validation before rendering
     *
     * @param array<string> $templates Array of template paths to try loading in priority order
     * @param array<string, mixed> $variables Variables to pass to the template for rendering
     * @param string $basePath Base path to prepend to template paths (legacy parameter)
     * @param TemplateType|string $type Type of template being loaded (entry, category, item, matrix)
     * @param array<string> $allowedBeastmodeValues Array of allowed beastmode debug values
     *
     * @return string The rendered template content or empty string if no template found
     *
     * @throws SyntaxError If template syntax is invalid
     * @throws Exception If template cannot be found or other Craft errors occur
     * @throws RuntimeError If template runtime error occurs during rendering
     * @throws LoaderError If template cannot be loaded by Twig
     * @throws InvalidArgumentException If invalid parameters are provided
     */
    public static function load(array $templates, array $variables, string $basePath, TemplateType|string $type = 'entry', array $allowedBeastmodeValues = []): string
    {
        // Validate input parameters
        if (!is_array($templates)) {
            throw new InvalidArgumentException("Templates must be an array");
        }

        if (empty($templates)) {
            throw new TemplateNotFoundException(
                attemptedPaths: [],
                templateType: $templateType,
                message: 'No template paths provided for resolution'
            );
        }

        // Convert string type to enum if needed
        $templateType = $type instanceof TemplateType ? $type : TemplateType::fromString((string) $type);
        
        $isDev = Craft::$app->getConfig()->general->devMode;

        // Get the directory from variables or type
        $directory = (string) ($variables['path'] ?? $templateType->getDefaultPath());

        // Generate cache key based on template info
        $cacheKey = 'load:' . implode(',', $templates) . ':' . $templateType->value;
        
        // Try to get cached version first
        $result = self::getCached($cacheKey);
        if ($result) {
            return $result;
        }

        // If no cache or dev mode, process templates
        if ($result === false) {
            $attemptedPaths = [];
            
            foreach ($templates as $template) {
                // Sanitize template path for security
                $sanitizedTemplate = self::sanitizeTemplatePath($template);
                
                // Use template path as is, since it's already properly formatted
                $fullPath = $basePath ? StringHelper::trim($basePath . '/' . $sanitizedTemplate, '/') : StringHelper::trim($sanitizedTemplate, '/');
                $attemptedPaths[] = $fullPath;
                
                // Check if template exists before trying to render
                if (Craft::$app->view->doesTemplateExist($fullPath)) {
                    $content = Craft::$app->view->renderTemplate($fullPath, $variables);
                    
                    // In production, return content directly
                    if (!$isDev) {
                        return $content;
                    }

                    // Dev mode: Check beastmode parameter
                    $beastmodeValue = Craft::$app->request->getParam('beastmode');
                    $debugMode = DebugMode::fromString($beastmodeValue);
                    
                    $shouldShowDebug = $debugMode->isEnabled() && (
                        $beastmodeValue === '' || // Empty value means show all
                        DebugMode::isValidForTemplateType((string) $beastmodeValue, $templateType) // Check template-specific values
                    );

                    // If debug is enabled, prepare debug info
                    if ($shouldShowDebug) {
                        // Process templates to remove directory prefix for display
                        $displayTemplates = array_map(function(string $path) use ($directory): string {
                            // Don't modify paths that already have the directory prefix
                            return $path;
                        }, $templates);

                        $info = [
                            'directory' => $directory,
                            'templates' => $displayTemplates,
                            'currentTemplate' => $fullPath,
                            'type' => $templateType->value,
                        ];
                        
                        // Wrap content with debug info
                        $content = self::renderInfo($content, Json::encode($info), $templateType->value);
                    }

                    // Cache the result for an hour
                    Craft::$app->cache->set($cacheKey, $content, 3600);
                    return $content;
                }
            }
        }

        // No template was found - create detailed exception
        $templateNotFoundException = new TemplateNotFoundException(
            attemptedPaths: $attemptedPaths ?? $templates,
            templateType: $templateType
        );
        
        // Log error with context
        Craft::error($templateNotFoundException->getMessage(), __METHOD__);

        // In dev mode, throw exception with detailed info
        if ($isDev) {
            throw $templateNotFoundException;
        }

        // In production, return empty string
        return '';
    }

    /**
     * Attempts to retrieve cached template content.
     *
     * In production mode, this method checks the cache for previously rendered
     * template content to improve performance. In development mode, caching is
     * disabled to ensure template changes are immediately visible.
     *
     * @param string $key Cache key to lookup in the application cache
     * @return string|false Cached template content or false if not found/dev mode
     */
    private static function getCached(string $key): string|false
    {
        if (Craft::$app->getConfig()->general->devMode) {
            return false;
        }
        return Craft::$app->cache->get($key);
    }

    /**
     * Renders debug information around template content in development mode.
     *
     * This method wraps the rendered template content with debug information
     * including the template resolution hierarchy, current template path, and
     * performance metrics. Only used when beastmode debugging is enabled.
     *
     * @param string $content Template content to wrap with debug information
     * @param string $info JSON encoded debug information containing paths and metadata
     * @param string $type Template type identifier (entry, category, item, matrix)
     *
     * @return string Content wrapped with debug information template
     */
    private static function renderInfo(string $content, string $info, string $type = 'entry'): string
    {
        // Render debug info template with content
        return Craft::$app->view->renderTemplate(
            template: '_bonsai-twig/_partials/infobar',
            variables: [
                'info' => $info,
                'content' => $content,
                'type' => $type,
            ]
        );
    }

    /**
     * Sanitizes a template path to prevent security issues.
     *
     * This method removes path traversal attempts and normalizes the path
     * to ensure it's safe for template loading. Throws an exception if
     * the path contains dangerous patterns.
     *
     * @param string $path Template path to sanitize
     * @return string Sanitized template path
     * @throws InvalidTemplatePathException If path contains dangerous patterns
     */
    private static function sanitizeTemplatePath(string $path): string
    {
        // Check for path traversal attempts
        if (str_contains($path, '../') || str_contains($path, '..\\')) {
            throw new InvalidTemplatePathException(
                path: $path,
                reason: 'Path traversal attempt detected'
            );
        }

        // Check for absolute paths (security risk)
        if (str_starts_with($path, '/') || (PHP_OS_FAMILY === 'Windows' && preg_match('/^[A-Za-z]:/', $path))) {
            throw new InvalidTemplatePathException(
                path: $path,
                reason: 'Absolute paths are not allowed'
            );
        }

        // Normalize path separators
        $path = str_replace('\\', '/', $path);
        
        // Remove leading/trailing slashes and spaces
        $path = trim($path, '/ ');

        // Validate that path is not empty after sanitization
        if (empty($path)) {
            throw new InvalidTemplatePathException(
                path: $path,
                reason: 'Path is empty after sanitization'
            );
        }

        return $path;
    }

    /**
     * Validates if a string contains valid JSON data.
     *
     * This utility method checks if the provided string is valid JSON by attempting
     * to decode it and checking for JSON parsing errors. Used for validating debug
     * information before processing.
     *
     * @param mixed $string String to validate for JSON format
     * @return bool True if the string contains valid JSON, false otherwise
     */
    private static function isJson(mixed $string): bool
    {
        return is_string($string) && json_decode($string) && json_last_error() === JSON_ERROR_NONE;
    }
}
