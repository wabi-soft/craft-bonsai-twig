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
     * - Optimized path deduplication and early exit strategies
     * - Batch file system operations for improved performance
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
        
        // Validate and sanitize input parameters
        $validatedTemplates = InputValidator::validateTemplatePaths($templates);
        $validatedVariables = InputValidator::validateTemplateVariables($variables);
        $validatedBasePath = InputValidator::validateString($basePath, 'basePath', false, 255);

        // Initialize env flags and services before any early exit
        $isDev = Craft::$app->getConfig()->general->devMode;
        $plugin = BonsaiTwig::getInstance();
        $cacheService = $plugin->cacheService;
        $performanceMonitor = $plugin->performanceMonitor;
        $errorReportingService = $plugin->errorReportingService;

        if (empty($validatedTemplates)) {
            $templateNotFoundException = new TemplateNotFoundException(
                attemptedPaths: [],
                templateType: $templateType,
                message: 'No template paths provided for resolution'
            );
            
            // Generate comprehensive error report
            $errorReport = $errorReportingService->generateTemplateNotFoundReport(
                $templateNotFoundException,
                null,
                ['input_templates' => $templates, 'base_path' => $basePath]
            );
            
            // Log error with detailed context
            $errorReportingService->logErrorReport($errorReport, 'error');
            
            // In dev mode, provide detailed error information
            if ($isDev) {
                $detailedMessage = $errorReportingService->formatErrorForDisplay($errorReport);
                throw new TemplateNotFoundException(
                    attemptedPaths: [],
                    templateType: $templateType,
                    message: $detailedMessage
                );
            }
            
            throw $templateNotFoundException;
        }

        // ...rest of method remains unchanged (with no duplicate initialization)
        
        $isDev = Craft::$app->getConfig()->general->devMode;

        // Get services for enhanced caching and performance monitoring
        $plugin = BonsaiTwig::getInstance();
        $cacheService = $plugin->cacheService;
        $performanceMonitor = $plugin->performanceMonitor;
        $errorReportingService = $plugin->errorReportingService;

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

        // If no cache or dev mode, process templates with optimized resolution
        if ($result === false) {
            $performanceMonitor->addCheckpoint($sessionId, 'start_resolution');
            
            // Optimize path generation and deduplication
            $optimizedPaths = self::optimizeTemplatePaths($validatedTemplates, $validatedBasePath);
            $performanceMonitor->addCheckpoint($sessionId, 'paths_optimized', [
                'original_count' => count($validatedTemplates),
                'optimized_count' => count($optimizedPaths)
            ]);
            
            // Enhance paths for multi-site template handling if context is available
            $fallbackSite = null;
            if (isset($templateContext)) {
                $siteEnhancementResult = self::enhancePathsForMultiSite($optimizedPaths, $templateContext);
                $optimizedPaths = $siteEnhancementResult['paths'];
                $fallbackSite = $siteEnhancementResult['fallbackSite'];
                
                $performanceMonitor->addCheckpoint($sessionId, 'site_paths_enhanced', [
                    'fallback_site' => $fallbackSite,
                    'enhanced_count' => count($optimizedPaths)
                ]);
                
                // Try site-specific cache first
                $siteSpecificCache = $cacheService->getCachedSiteSpecificTemplateResolution($templateContext, $optimizedPaths);
                if ($siteSpecificCache !== null) {
                    $performanceMonitor->recordCacheAccess(true, 'site_template');
                    $performanceMonitor->endTiming($sessionId);
                    return $siteSpecificCache['resolvedPath'] ? 
                        Craft::$app->view->renderTemplate($siteSpecificCache['resolvedPath'], $validatedVariables) : '';
                }
                $performanceMonitor->recordCacheAccess(false, 'site_template');
            }
            
            // Early exit: Check if we have any paths to process
            if (empty($optimizedPaths)) {
                $performanceData = $performanceMonitor->endTiming($sessionId);
                $performanceMonitor->recordTemplateResolution(false, $performanceData['total_time'] ?? 0.0, 0);
                
                $templateNotFoundException = new TemplateNotFoundException(
                    attemptedPaths: $validatedTemplates,
                    templateType: $templateType,
                    message: 'No valid template paths after optimization'
                );
                
                // Generate comprehensive error report
                $errorReport = $errorReportingService->generateTemplateNotFoundReport(
                    $templateNotFoundException,
                    $templateContext ?? null,
                    [
                        'original_templates' => $validatedTemplates,
                        'optimization_failed' => true,
                        'performance_data' => $performanceData
                    ]
                );
                
                // Log error with detailed context
                $errorReportingService->logErrorReport($errorReport, 'warning');
                
                if ($isDev) {
                    $detailedMessage = $errorReportingService->formatErrorForDisplay($errorReport);
                    throw new TemplateNotFoundException(
                        attemptedPaths: $validatedTemplates,
                        templateType: $templateType,
                        message: $detailedMessage
                    );
                }
                return '';
            }
            
            // Batch template existence checks for better performance
            $existenceResults = self::batchCheckTemplateExistence($optimizedPaths, $cacheService, $performanceMonitor);
            $performanceMonitor->addCheckpoint($sessionId, 'existence_checked');
            
            // Find first existing template using early exit strategy
            $resolvedPath = self::findFirstExistingTemplate($optimizedPaths, $existenceResults);
            
            if ($resolvedPath !== null) {
                $performanceMonitor->addCheckpoint($sessionId, 'template_found', ['path' => $resolvedPath]);
                
                // Render the template
                $content = Craft::$app->view->renderTemplate($resolvedPath, $validatedVariables);
                $performanceMonitor->addCheckpoint($sessionId, 'template_rendered');
                
                // In production, cache and return content directly
                if (!$isDev) {
                    // Cache using enhanced caching service
                    if (isset($templateContext)) {
                        // Use site-specific caching if fallback site was used
                        if ($fallbackSite !== null) {
                            $cacheService->cacheSiteSpecificTemplateResolution(
                                $templateContext,
                                $optimizedPaths,
                                $resolvedPath,
                                $fallbackSite,
                                ['cached_at' => time()]
                            );
                        } else {
                            $cacheService->cacheTemplateResolution(
                                $templateContext,
                                $optimizedPaths,
                                $resolvedPath,
                                ['cached_at' => time()]
                            );
                        }
                    }
                    
                    // Legacy cache for backward compatibility
                    Craft::$app->cache->set($cacheKey, $content, 3600);
                    
                    // End performance monitoring
                    $performanceData = $performanceMonitor->endTiming($sessionId);
                    $performanceMonitor->recordTemplateResolution(true, $performanceData['total_time'] ?? 0.0, count($optimizedPaths));
                    
                    return $content;
                }

                // Dev mode: Check beastmode parameter with validation
                $beastmodeValue = Craft::$app->request->getParam('beastmode');
                $debugMode = InputValidator::validateDebugMode($beastmodeValue, $templateType);
                
                // Show debug info only for matching template types or general debug modes
                $shouldShowDebug = $debugMode->isEnabled() && 
                    DebugMode::isValidForTemplateType((string) $beastmodeValue, $templateType);

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

                    // Determine element kind for debug (entry vs category) when available
                    $elementKind = null;
                    if (isset($templateContext) && $templateContext->element) {
                        $el = $templateContext->element;
                        $elementKind = ($el instanceof \craft\elements\Category) ? 'category' : (($el instanceof \craft\elements\Entry) ? 'entry' : null);
                    } elseif (isset($validatedVariables['entry']) && $validatedVariables['entry'] instanceof \craft\base\Element) {
                        $el = $validatedVariables['entry'];
                        $elementKind = ($el instanceof \craft\elements\Category) ? 'category' : (($el instanceof \craft\elements\Entry) ? 'entry' : null);
                    }

                    $info = [
                        'directory' => $directory,
                        'templates' => $displayTemplates,
                        'optimized_templates' => $optimizedPaths,
                        'currentTemplate' => $resolvedPath,
                        'type' => $templateType->value,
                        'element_kind' => $elementKind,
                        'site_info' => [
                            'current_site' => Craft::$app->sites->currentSite->handle,
                            'element_site' => isset($templateContext) ? ($templateContext->element->site->handle ?? null) : null,
                            'base_site' => isset($templateContext) ? $templateContext->baseSite : null,
                            'fallback_site' => $fallbackSite ?? null,
                        ],
                        'performance' => [
                            'resolution_time' => $performanceData['total_time'] ?? 0.0,
                            'memory_usage' => $performanceData['memory_usage'] ?? [],
                            'checkpoints' => $performanceData['checkpoints'] ?? [],
                            'optimization_savings' => count($validatedTemplates) - count($optimizedPaths),
                        ],
                        'cache_stats' => $performanceMonitor->getCacheStatistics(),
                    ];
                    
                    // Wrap content with enhanced debug info
                    $displayType = $templateType->value . ($elementKind ? (' (' . $elementKind . ')') : '');
                    $content = self::renderInfo($content, Json::encode($info), $displayType);
                    
                    $performanceMonitor->recordTemplateResolution(true, $performanceData['total_time'] ?? 0.0, count($optimizedPaths));
                } else {
                    // End timing without debug info
                    $performanceData = $performanceMonitor->endTiming($sessionId);
                    $performanceMonitor->recordTemplateResolution(true, $performanceData['total_time'] ?? 0.0, count($optimizedPaths));
                }

                // Cache the result for an hour (legacy cache)
                Craft::$app->cache->set($cacheKey, $content, 3600);
                
                // Enhanced caching
                if (isset($templateContext)) {
                    // Use site-specific caching if fallback site was used
                    if ($fallbackSite !== null) {
                        $cacheService->cacheSiteSpecificTemplateResolution(
                            $templateContext,
                            $optimizedPaths,
                            $resolvedPath,
                            $fallbackSite,
                            ['cached_at' => time(), 'debug_enabled' => $shouldShowDebug ?? false]
                        );
                    } else {
                        $cacheService->cacheTemplateResolution(
                            $templateContext,
                            $optimizedPaths,
                            $resolvedPath,
                            ['cached_at' => time(), 'debug_enabled' => $shouldShowDebug ?? false]
                        );
                    }
                }
                
                return $content;
            }
        }

        // No template was found - end performance monitoring and handle error
        $performanceData = $performanceMonitor->endTiming($sessionId);
        $finalAttemptedPaths = $optimizedPaths ?? $validatedTemplates;
        $performanceMonitor->recordTemplateResolution(false, $performanceData['total_time'] ?? 0.0, count($finalAttemptedPaths));
        
        // Create detailed exception
        $templateNotFoundException = new TemplateNotFoundException(
            attemptedPaths: $finalAttemptedPaths,
            templateType: $templateType
        );
        
        // Generate comprehensive error report
        $errorReport = $errorReportingService->generateTemplateNotFoundReport(
            $templateNotFoundException,
            $templateContext ?? null,
            [
                'original_templates' => $validatedTemplates,
                'optimized_templates' => $optimizedPaths ?? [],
                'performance_data' => $performanceData,
                'fallback_site' => $fallbackSite ?? null,
                'cache_attempts' => [
                    'enhanced_cache' => isset($templateContext),
                    'legacy_cache' => true,
                    'site_specific_cache' => isset($templateContext) && isset($fallbackSite)
                ]
            ]
        );
        
        // Log error with comprehensive context
        $errorReportingService->logErrorReport($errorReport, 'error');

        // Cache negative result to avoid repeated failed lookups
        if (isset($templateContext)) {
            // Use site-specific caching if fallback site was attempted
            if (isset($fallbackSite) && $fallbackSite !== null) {
                $cacheService->cacheSiteSpecificTemplateResolution(
                    $templateContext,
                    $finalAttemptedPaths,
                    null,
                    $fallbackSite,
                    ['failed_at' => time(), 'error' => $templateNotFoundException->getMessage(), 'error_report' => $errorReport]
                );
            } else {
                $cacheService->cacheTemplateResolution(
                    $templateContext,
                    $finalAttemptedPaths,
                    null,
                    ['failed_at' => time(), 'error' => $templateNotFoundException->getMessage(), 'error_report' => $errorReport]
                );
            }
        }

        // In dev mode, throw exception with detailed info
        if ($isDev) {
            $detailedMessage = $errorReportingService->formatErrorForDisplay($errorReport);
            throw new TemplateNotFoundException(
                attemptedPaths: $finalAttemptedPaths,
                templateType: $templateType,
                message: $detailedMessage
            );
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
        // Check plugin setting for caching in dev mode
        $plugin = BonsaiTwig::getInstance();
        $isDev = Craft::$app->getConfig()->general->devMode;
        
        if ($isDev && (!$plugin || !$plugin->getSettings() || !$plugin->getSettings()->cacheInDevMode)) {
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

    /**
     * Optimizes template paths by deduplication and normalization.
     *
     * This method implements efficient path deduplication algorithms to reduce
     * the number of file system checks needed during template resolution.
     * It maintains the original order while removing duplicates and normalizing paths.
     *
     * @param array<string> $templates Original template paths
     * @param string $basePath Base path to prepend to template paths
     * @return array<string> Optimized and deduplicated template paths
     */
    private static function optimizeTemplatePaths(array $templates, string $basePath): array
    {
        if (empty($templates)) {
            return [];
        }

        $optimizedPaths = [];
        $seenPaths = [];
        
        foreach ($templates as $template) {
            // Generate full path
            $fullPath = $basePath ? StringHelper::trim($basePath . '/' . $template, '/') : StringHelper::trim($template, '/');
            
            // Validate the full path before using it
            try {
                SecurityUtils::validateTemplatePath($fullPath);
            } catch (InvalidTemplatePathException $e) {
                // Skip invalid paths but continue processing
                continue;
            }
            
            // Normalize path for deduplication (convert backslashes, remove double slashes)
            $normalizedPath = str_replace(['\\', '//'], ['/', '/'], $fullPath);
            $normalizedPath = rtrim($normalizedPath, '/');
            
            // Skip if we've already seen this path
            if (isset($seenPaths[$normalizedPath])) {
                continue;
            }
            
            $seenPaths[$normalizedPath] = true;
            $optimizedPaths[] = $normalizedPath;
        }
        
        return $optimizedPaths;
    }

    /**
     * Performs batch template existence checks for improved performance.
     *
     * This method optimizes file system operations by batching template existence
     * checks and leveraging caching to minimize redundant file system access.
     *
     * @param array<string> $templatePaths Template paths to check
     * @param CacheService $cacheService Cache service for storing results
     * @param PerformanceMonitor $performanceMonitor Performance monitoring service
     * @return array<string, bool> Map of template paths to existence status
     */
    private static function batchCheckTemplateExistence(
        array $templatePaths,
        CacheService $cacheService,
        PerformanceMonitor $performanceMonitor
    ): array {
        $existenceResults = [];
        $uncachedPaths = [];
        
        // First pass: Check cache for all paths
        foreach ($templatePaths as $path) {
            $cachedResult = $cacheService->getCachedTemplateExistence($path);
            if ($cachedResult !== null) {
                $existenceResults[$path] = $cachedResult;
                $performanceMonitor->recordCacheAccess(true, 'existence');
            } else {
                $uncachedPaths[] = $path;
            }
        }
        
        // Second pass: Batch check uncached paths
        if (!empty($uncachedPaths)) {
            $view = Craft::$app->view;
            
            foreach ($uncachedPaths as $path) {
                $exists = $view->doesTemplateExist($path);
                $existenceResults[$path] = $exists;
                
                // Cache the result for future use
                $cacheService->cacheTemplateExistence($path, $exists);
                $performanceMonitor->recordCacheAccess(false, 'existence');
            }
        }
        
        return $existenceResults;
    }

    /**
     * Finds the first existing template using early exit strategy.
     *
     * This method implements an early exit strategy to stop processing as soon
     * as the first existing template is found, improving performance for cases
     * where templates are found early in the hierarchy.
     *
     * @param array<string> $templatePaths Template paths in priority order
     * @param array<string, bool> $existenceResults Pre-computed existence results
     * @return string|null Path of first existing template or null if none found
     */
    private static function findFirstExistingTemplate(
        array $templatePaths,
        array $existenceResults
    ): ?string {
        foreach ($templatePaths as $path) {
            if ($existenceResults[$path] ?? false) {
                return $path;
            }
        }
        
        return null;
    }

    /**
     * Enhances template paths with site-specific variations and fallback mechanisms.
     *
     * This method implements multi-site template resolution logic by generating
     * site-specific template paths and proper fallback mechanisms for missing
     * site templates.
     *
     * @param array<string> $templatePaths Original template paths
     * @param TemplateContext $context Template resolution context
     * @return array{paths: array<string>, fallbackSite: string|null} Enhanced paths with fallback info
     */
    private static function enhancePathsForMultiSite(
        array $templatePaths,
        TemplateContext $context
    ): array {
        $sitesService = Craft::$app->sites;
        $currentSite = $sitesService->currentSite;
        $fallbackSite = null;
        
        // Get element's site or current site
        $elementSiteId = $context->element->siteId ?? $currentSite->id;
        $elementSite = $sitesService->getSiteById($elementSiteId);
        
        if (!$elementSite) {
            $elementSite = $currentSite;
        }
        
        $enhancedPaths = [];
        
        // If baseSite is specified, use it for site-specific paths
        if ($context->baseSite) {
            $baseSite = $sitesService->getSiteByHandle($context->baseSite);
            if ($baseSite) {
                // Generate site-specific paths first (highest priority)
                foreach ($templatePaths as $path) {
                    $siteSpecificPath = self::generateSiteSpecificPath($path, $baseSite->handle);
                    if ($siteSpecificPath !== $path) {
                        $enhancedPaths[] = $siteSpecificPath;
                    }
                }
                
                // Add original paths as fallback
                $enhancedPaths = array_merge($enhancedPaths, $templatePaths);
                
                // If baseSite is different from element site, set up fallback
                if ($baseSite->id !== $elementSite->id) {
                    $fallbackSite = $elementSite->handle;
                    
                    // Add element site-specific paths as additional fallback
                    foreach ($templatePaths as $path) {
                        $fallbackPath = self::generateSiteSpecificPath($path, $elementSite->handle);
                        if ($fallbackPath !== $path && !in_array($fallbackPath, $enhancedPaths)) {
                            $enhancedPaths[] = $fallbackPath;
                        }
                    }
                }
            } else {
                // Invalid baseSite handle, use original paths
                $enhancedPaths = $templatePaths;
            }
        } else {
            // No baseSite specified, use element's site for site-specific paths
            foreach ($templatePaths as $path) {
                $siteSpecificPath = self::generateSiteSpecificPath($path, $elementSite->handle);
                if ($siteSpecificPath !== $path) {
                    $enhancedPaths[] = $siteSpecificPath;
                }
            }
            
            // Add original paths as fallback
            $enhancedPaths = array_merge($enhancedPaths, $templatePaths);
            
            // If element site is not the primary site, add primary site fallback
            $primarySite = $sitesService->primarySite;
            if ($primarySite && $primarySite->id !== $elementSite->id) {
                $fallbackSite = $primarySite->handle;
                
                foreach ($templatePaths as $path) {
                    $primarySitePath = self::generateSiteSpecificPath($path, $primarySite->handle);
                    if ($primarySitePath !== $path && !in_array($primarySitePath, $enhancedPaths)) {
                        $enhancedPaths[] = $primarySitePath;
                    }
                }
            }
        }
        
        return [
            'paths' => array_unique($enhancedPaths),
            'fallbackSite' => $fallbackSite,
        ];
    }

    /**
     * Generates a site-specific template path.
     *
     * This method creates site-specific template paths by inserting the site handle
     * into the template path structure, following Craft CMS conventions.
     *
     * @param string $templatePath Original template path
     * @param string $siteHandle Site handle to use for path generation
     * @return string Site-specific template path
     */
    private static function generateSiteSpecificPath(string $templatePath, string $siteHandle): string
    {
        // Don't modify paths that already contain site-specific segments
        if (str_contains($templatePath, '/_' . $siteHandle . '/') || 
            str_contains($templatePath, '/' . $siteHandle . '/')) {
            return $templatePath;
        }
        
        // Split path into directory and filename
        $pathParts = explode('/', $templatePath);
        $filename = array_pop($pathParts);
        $directory = implode('/', $pathParts);
        
        // Generate site-specific variations
        $siteSpecificPaths = [];
        
        // Method 1: Add site handle as subdirectory
        if (!empty($directory)) {
            $siteSpecificPaths[] = $directory . '/' . $siteHandle . '/' . $filename;
        } else {
            $siteSpecificPaths[] = $siteHandle . '/' . $filename;
        }
        
        // Method 2: Add site handle as filename prefix (for flat structures)
        $filenameParts = explode('.', $filename);
        if (count($filenameParts) > 1) {
            $extension = array_pop($filenameParts);
            $baseName = implode('.', $filenameParts);
            $siteSpecificFilename = $baseName . '_' . $siteHandle . '.' . $extension;
            
            if (!empty($directory)) {
                $siteSpecificPaths[] = $directory . '/' . $siteSpecificFilename;
            } else {
                $siteSpecificPaths[] = $siteSpecificFilename;
            }
        }
        
        // Return the first site-specific path (subdirectory method preferred)
        return $siteSpecificPaths[0] ?? $templatePath;
    }
}
