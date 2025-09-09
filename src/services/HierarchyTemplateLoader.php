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
use wabisoft\bonsaitwig\utilities\SecurityUtils;
use wabisoft\bonsaitwig\utilities\InputValidator;
use wabisoft\bonsaitwig\valueobjects\TemplateContext;
use wabisoft\bonsaitwig\BonsaiTwig;
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
        // Convert string type to enum if needed
        $templateType = $type instanceof TemplateType ? $type : TemplateType::fromString((string) $type);
        
        // Validate and sanitize input parameters
        $validatedTemplates = InputValidator::validateTemplatePaths($templates);
        $validatedVariables = InputValidator::validateTemplateVariables($variables);
        $validatedBasePath = InputValidator::validateString($basePath, 'basePath', false, 255);
        
        if (empty($validatedTemplates)) {
            throw new TemplateNotFoundException(
                attemptedPaths: [],
                templateType: $templateType,
                message: 'No template paths provided for resolution'
            );
        }
        
        $isDev = Craft::$app->getConfig()->general->devMode;

        // Get services for enhanced caching and performance monitoring
        $plugin = BonsaiTwig::getInstance();
        $cacheService = $plugin->cacheService;
        $performanceMonitor = $plugin->performanceMonitor;

        // Start performance monitoring
        $sessionId = 'template_resolution_' . uniqid();
        $performanceMonitor->startTiming($sessionId, 'Template Resolution', [
            'template_type' => $templateType->value,
            'template_count' => count($validatedTemplates),
            'base_path' => $validatedBasePath,
        ]);

        // Get the directory from variables or type
        $directory = (string) ($validatedVariables['path'] ?? $templateType->getDefaultPath());

        // Create template context for enhanced caching
        $element = $validatedVariables['element'] ?? null;
        if ($element) {
            $templateContext = new TemplateContext(
                element: $element,
                path: $directory,
                style: $validatedVariables['style'] ?? null,
                context: $validatedVariables['context'] ?? null,
                baseSite: $validatedVariables['baseSite'] ?? null,
                variables: $validatedVariables,
                showDebug: $isDev
            );

            // Try enhanced cache first
            $cachedResult = $cacheService->getCachedTemplateResolution($templateContext, $validatedTemplates);
            if ($cachedResult !== null) {
                $performanceMonitor->recordCacheAccess(true, 'template');
                $performanceMonitor->endTiming($sessionId);
                return $cachedResult['resolvedPath'] ? 
                    Craft::$app->view->renderTemplate($cachedResult['resolvedPath'], $validatedVariables) : '';
            }
            $performanceMonitor->recordCacheAccess(false, 'template');
        }

        // Fallback to legacy cache key for backward compatibility
        $cacheKey = SecurityUtils::generateSecureCacheKey($validatedTemplates, $templateType->value, ['directory' => $directory]);
        
        // Try to get cached version first
        $result = self::getCached($cacheKey);
        if ($result) {
            $performanceMonitor->recordCacheAccess(true, 'legacy');
            $performanceMonitor->endTiming($sessionId);
            return $result;
        }
        $performanceMonitor->recordCacheAccess(false, 'legacy');

        // If no cache or dev mode, process templates
        if ($result === false) {
            $attemptedPaths = [];
            $resolvedPath = null;
            
            $performanceMonitor->addCheckpoint($sessionId, 'start_resolution');
            
            foreach ($validatedTemplates as $template) {
                // Templates are already sanitized by InputValidator::validateTemplatePaths
                // Use template path as is, since it's already properly formatted
                $fullPath = $validatedBasePath ? StringHelper::trim($validatedBasePath . '/' . $template, '/') : StringHelper::trim($template, '/');
                
                // Validate the full path before using it
                SecurityUtils::validateTemplatePath($fullPath);
                $attemptedPaths[] = $fullPath;
                
                // Check cached template existence first
                $templateExists = $cacheService->getCachedTemplateExistence($fullPath);
                if ($templateExists === null) {
                    // Not cached, check and cache the result
                    $templateExists = Craft::$app->view->doesTemplateExist($fullPath);
                    $cacheService->cacheTemplateExistence($fullPath, $templateExists);
                    $performanceMonitor->recordCacheAccess(false, 'existence');
                } else {
                    $performanceMonitor->recordCacheAccess(true, 'existence');
                }
                
                // Check if template exists before trying to render
                if ($templateExists) {
                    $performanceMonitor->addCheckpoint($sessionId, 'template_found', ['path' => $fullPath]);
                    
                    $resolvedPath = $fullPath;
                    $content = Craft::$app->view->renderTemplate($fullPath, $validatedVariables);
                    
                    $performanceMonitor->addCheckpoint($sessionId, 'template_rendered');
                    
                    // In production, cache and return content directly
                    if (!$isDev) {
                        // Cache using enhanced caching service
                        if (isset($templateContext)) {
                            $cacheService->cacheTemplateResolution(
                                $templateContext,
                                $attemptedPaths,
                                $resolvedPath,
                                ['cached_at' => time()]
                            );
                        }
                        
                        // Legacy cache for backward compatibility
                        Craft::$app->cache->set($cacheKey, $content, 3600);
                        
                        // End performance monitoring
                        $performanceData = $performanceMonitor->endTiming($sessionId);
                        $performanceMonitor->recordTemplateResolution(true, $performanceData['total_time'] ?? 0.0, count($attemptedPaths));
                        
                        return $content;
                    }

                    // Dev mode: Check beastmode parameter with validation
                    $beastmodeValue = Craft::$app->request->getParam('beastmode');
                    $debugMode = InputValidator::validateDebugMode($beastmodeValue, $templateType);
                    
                    $shouldShowDebug = $debugMode->isEnabled() && (
                        $beastmodeValue === '' || // Empty value means show all
                        DebugMode::isValidForTemplateType((string) $beastmodeValue, $templateType) // Check template-specific values
                    );

                    // If debug is enabled, prepare debug info with performance metrics
                    if ($shouldShowDebug) {
                        $performanceMonitor->addCheckpoint($sessionId, 'debug_info_start');
                        
                        // Get performance data before ending timing
                        $performanceData = $performanceMonitor->endTiming($sessionId);
                        
                        // Process templates to remove directory prefix for display
                        $displayTemplates = array_map(function(string $path) use ($directory): string {
                            // Don't modify paths that already have the directory prefix
                            return $path;
                        }, $validatedTemplates);

                        $info = [
                            'directory' => $directory,
                            'templates' => $displayTemplates,
                            'currentTemplate' => $fullPath,
                            'type' => $templateType->value,
                            'performance' => [
                                'resolution_time' => $performanceData['total_time'] ?? 0.0,
                                'memory_usage' => $performanceData['memory_usage'] ?? [],
                                'checkpoints' => $performanceData['checkpoints'] ?? [],
                            ],
                            'cache_stats' => $performanceMonitor->getCacheStatistics(),
                        ];
                        
                        // Wrap content with enhanced debug info
                        $content = self::renderInfo($content, Json::encode($info), $templateType->value);
                        
                        $performanceMonitor->recordTemplateResolution(true, $performanceData['total_time'] ?? 0.0, count($attemptedPaths));
                    } else {
                        // End timing without debug info
                        $performanceData = $performanceMonitor->endTiming($sessionId);
                        $performanceMonitor->recordTemplateResolution(true, $performanceData['total_time'] ?? 0.0, count($attemptedPaths));
                    }

                    // Cache the result for an hour (legacy cache)
                    Craft::$app->cache->set($cacheKey, $content, 3600);
                    
                    // Enhanced caching
                    if (isset($templateContext)) {
                        $cacheService->cacheTemplateResolution(
                            $templateContext,
                            $attemptedPaths,
                            $resolvedPath,
                            ['cached_at' => time(), 'debug_enabled' => $shouldShowDebug ?? false]
                        );
                    }
                    
                    return $content;
                }
            }
        }

        // No template was found - end performance monitoring and handle error
        $performanceData = $performanceMonitor->endTiming($sessionId);
        $performanceMonitor->recordTemplateResolution(false, $performanceData['total_time'] ?? 0.0, count($attemptedPaths ?? $validatedTemplates));
        
        // Create detailed exception
        $templateNotFoundException = new TemplateNotFoundException(
            attemptedPaths: $attemptedPaths ?? $validatedTemplates,
            templateType: $templateType
        );
        
        // Log error with context
        Craft::error($templateNotFoundException->getMessage(), __METHOD__);

        // Cache negative result to avoid repeated failed lookups
        if (isset($templateContext)) {
            $cacheService->cacheTemplateResolution(
                $templateContext,
                $attemptedPaths ?? $validatedTemplates,
                null,
                ['failed_at' => time(), 'error' => $templateNotFoundException->getMessage()]
            );
        }

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
