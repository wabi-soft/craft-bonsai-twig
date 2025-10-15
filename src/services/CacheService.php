<?php

namespace wabisoft\bonsaitwig\services;

use Craft;
use craft\base\Element;
use wabisoft\bonsaitwig\enums\TemplateType;
use wabisoft\bonsaitwig\utilities\SecurityUtils;
use wabisoft\bonsaitwig\valueobjects\TemplateContext;
use wabisoft\bonsaitwig\BonsaiTwig;
use yii\base\Component;
use yii\caching\TagDependency;

/**
 * Enhanced caching service for template resolution and element properties.
 *
 * This service provides intelligent caching strategies for template path resolution,
 * element property access, and template existence checks. It implements proper cache
 * invalidation, secure key generation, and performance optimizations.
 *
 * Features:
 * - Template path resolution caching with proper invalidation
 * - Element property caching for frequently accessed data
 * - Secure cache key generation with hashing
 * - Tag-based cache invalidation for related content
 * - Development mode cache bypass
 * - Performance monitoring integration
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\services
 * @since 6.4.0
 */
class CacheService extends Component
{
    /**
     * Cache duration for template path resolution (1 hour in production).
     */
    private const TEMPLATE_CACHE_DURATION = 3600;

    /**
     * Cache duration for element properties (30 minutes).
     */
    private const ELEMENT_CACHE_DURATION = 1800;

    /**
     * Cache duration for template existence checks (2 hours).
     */
    private const EXISTENCE_CACHE_DURATION = 7200;

    /**
     * Maximum number of cached items per category to prevent memory issues.
     */
    private const MAX_CACHE_ITEMS = 1000;

    /**
     * Cache key prefix for template resolution.
     */
    private const TEMPLATE_KEY_PREFIX = 'bonsai_twig:template:';

    /**
     * Cache key prefix for element properties.
     */
    private const ELEMENT_KEY_PREFIX = 'bonsai_twig:element:';

    /**
     * Cache key prefix for template existence.
     */
    private const EXISTENCE_KEY_PREFIX = 'bonsai_twig:exists:';

    /**
     * Caches the result of template path resolution.
     *
     * Stores the resolved template path and associated metadata for faster
     * subsequent lookups. Uses secure key generation and proper invalidation.
     *
     * @param TemplateContext $context Template resolution context
     * @param array<string> $attemptedPaths All paths that were attempted
     * @param string|null $resolvedPath The successfully resolved path (null if none found)
     * @param array<string, mixed> $metadata Additional metadata about resolution
     * @return void
     */
    public function cacheTemplateResolution(
        TemplateContext $context,
        array $attemptedPaths,
        ?string $resolvedPath,
        array $metadata = []
    ): void {
        // Skip caching in development mode unless plugin setting overrides it
        if ($this->shouldSkipCaching()) {
            return;
        }

        $cacheKey = $this->generateTemplateResolutionKey($context, $attemptedPaths);
        
        $cacheData = [
            'resolvedPath' => $resolvedPath,
            'attemptedPaths' => $attemptedPaths,
            'metadata' => $metadata,
            'timestamp' => time(),
            'elementId' => $context->element->id,
            'elementType' => get_class($context->element),
        ];

        // Generate cache tags for invalidation
        $tags = $this->generateCacheTags($context);

        Craft::$app->cache->set(
            $cacheKey,
            $cacheData,
            self::TEMPLATE_CACHE_DURATION,
            new TagDependency(['tags' => $tags])
        );
    }

    /**
     * Retrieves cached template resolution result.
     *
     * @param TemplateContext $context Template resolution context
     * @param array<string> $attemptedPaths All paths being attempted
     * @return array<string, mixed>|null Cached resolution data or null if not found
     */
    public function getCachedTemplateResolution(
        TemplateContext $context,
        array $attemptedPaths
    ): ?array {
        // Skip cache in development mode unless plugin setting overrides it
        if ($this->shouldSkipCaching()) {
            return null;
        }

        $cacheKey = $this->generateTemplateResolutionKey($context, $attemptedPaths);
        $cached = Craft::$app->cache->get($cacheKey);

        if ($cached === false) {
            return null;
        }

        // Validate cached data structure
        if (!is_array($cached) || !isset($cached['resolvedPath'], $cached['attemptedPaths'])) {
            return null;
        }

        return $cached;
    }

    /**
     * Caches frequently accessed element properties.
     *
     * Stores element property values that are expensive to compute or frequently
     * accessed during template resolution.
     *
     * @param Element $element The element whose properties to cache
     * @param string $property Property name being cached
     * @param mixed $value Property value to cache
     * @return void
     */
    public function cacheElementProperty(Element $element, string $property, mixed $value): void
    {
        // Skip caching in development mode unless plugin setting overrides it
        if ($this->shouldSkipCaching()) {
            return;
        }

        $cacheKey = $this->generateElementPropertyKey($element, $property);
        
        $cacheData = [
            'value' => $value,
            'timestamp' => time(),
            'elementId' => $element->id,
            'elementType' => get_class($element),
            'property' => $property,
        ];

        // Generate cache tags for invalidation
        $tags = [
            'element:' . $element->id,
            'elementType:' . get_class($element),
            'property:' . $property,
        ];

        Craft::$app->cache->set(
            $cacheKey,
            $cacheData,
            self::ELEMENT_CACHE_DURATION,
            new TagDependency(['tags' => $tags])
        );
    }

    /**
     * Retrieves cached element property value.
     *
     * @param Element $element The element whose property to retrieve
     * @param string $property Property name to retrieve
     * @return mixed|null Cached property value or null if not found
     */
    public function getCachedElementProperty(Element $element, string $property): mixed
    {
        // Skip cache in development mode unless plugin setting overrides it
        if ($this->shouldSkipCaching()) {
            return null;
        }

        $cacheKey = $this->generateElementPropertyKey($element, $property);
        $cached = Craft::$app->cache->get($cacheKey);

        if ($cached === false) {
            return null;
        }

        // Validate cached data structure
        if (!is_array($cached) || !array_key_exists('value', $cached)) {
            return null;
        }

        return $cached['value'];
    }

    /**
     * Caches template existence check results.
     *
     * Stores the result of template existence checks to avoid repeated
     * file system operations.
     *
     * @param string $templatePath Template path that was checked
     * @param bool $exists Whether the template exists
     * @return void
     */
    public function cacheTemplateExistence(string $templatePath, bool $exists): void
    {
        // Skip caching in development mode unless plugin setting overrides it
        if ($this->shouldSkipCaching()) {
            return;
        }

        $cacheKey = $this->generateTemplateExistenceKey($templatePath);
        
        $cacheData = [
            'exists' => $exists,
            'timestamp' => time(),
            'templatePath' => $templatePath,
        ];

        // Template existence can be cached longer since templates don't change often
        Craft::$app->cache->set($cacheKey, $cacheData, self::EXISTENCE_CACHE_DURATION);
    }

    /**
     * Retrieves cached template existence result.
     *
     * @param string $templatePath Template path to check
     * @return bool|null True if exists, false if doesn't exist, null if not cached
     */
    public function getCachedTemplateExistence(string $templatePath): ?bool
    {
        // Skip cache in development mode unless plugin setting overrides it
        if ($this->shouldSkipCaching()) {
            return null;
        }

        $cacheKey = $this->generateTemplateExistenceKey($templatePath);
        $cached = Craft::$app->cache->get($cacheKey);

        if ($cached === false) {
            return null;
        }

        // Validate cached data structure
        if (!is_array($cached) || !array_key_exists('exists', $cached)) {
            return null;
        }

        return $cached['exists'];
    }

    /**
     * Invalidates cache for a specific element.
     *
     * Removes all cached data related to a specific element when it's updated.
     *
     * @param Element $element Element whose cache to invalidate
     * @return void
     */
    public function invalidateElementCache(Element $element): void
    {
        $tags = [
            'element:' . $element->id,
            'elementType:' . get_class($element),
        ];

        TagDependency::invalidate(Craft::$app->cache, $tags);
    }

    /**
     * Invalidates cache for a specific template type.
     *
     * Removes all cached data related to a specific template type.
     *
     * @param TemplateType $templateType Template type whose cache to invalidate
     * @return void
     */
    public function invalidateTemplateTypeCache(TemplateType $templateType): void
    {
        $tags = ['templateType:' . $templateType->value];
        TagDependency::invalidate(Craft::$app->cache, $tags);
    }

    /**
     * Clears all plugin-related cache entries.
     *
     * Removes all cached data created by this plugin. Useful for debugging
     * or when major changes are made.
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        $tags = ['bonsai_twig'];
        TagDependency::invalidate(Craft::$app->cache, $tags);
    }

    /**
     * Generates a secure cache key for template resolution.
     *
     * @param TemplateContext $context Template resolution context
     * @param array<string> $attemptedPaths All paths being attempted
     * @return string Secure cache key
     */
    private function generateTemplateResolutionKey(
        TemplateContext $context,
        array $attemptedPaths
    ): string {
        $currentSiteId = $context->element->siteId ?? Craft::$app->sites->currentSite->id;

        $keyData = [
            'elementId' => $context->element->id,
            'elementType' => get_class($context->element),
            'path' => $context->path,
            'style' => $context->style,
            'baseSite' => $context->baseSite,
            'currentSite' => $currentSiteId,
            'attemptedPaths' => $attemptedPaths,
            'contextElementId' => $context->context?->id,
        ];

        return self::TEMPLATE_KEY_PREFIX . SecurityUtils::generateSecureCacheKey(
            $attemptedPaths,
            'template_resolution',
            $keyData
        );
    }

    /**
     * Generates a secure cache key for element properties.
     *
     * @param Element $element Element whose property is being cached
     * @param string $property Property name
     * @return string Secure cache key
     */
    private function generateElementPropertyKey(Element $element, string $property): string
    {
        $keyData = [
            'elementId' => $element->id,
            'elementType' => get_class($element),
            'property' => $property,
        ];

        return self::ELEMENT_KEY_PREFIX . hash('sha256', serialize($keyData));
    }

    /**
     * Generates a secure cache key for template existence checks.
     *
     * @param string $templatePath Template path being checked
     * @return string Secure cache key
     */
    private function generateTemplateExistenceKey(string $templatePath): string
    {
        // Sanitize the path first for security
        $sanitizedPath = SecurityUtils::sanitizeTemplatePath($templatePath);
        
        return self::EXISTENCE_KEY_PREFIX . hash('sha256', $sanitizedPath);
    }

    /**
     * Caches site-specific template resolution result.
     *
     * Stores template resolution results with site-specific context for
     * multi-site template handling and proper fallback mechanisms.
     *
     * @param TemplateContext $context Template resolution context
     * @param array<string> $attemptedPaths All paths that were attempted
     * @param string|null $resolvedPath The successfully resolved path (null if none found)
     * @param string|null $fallbackSite Site used for fallback resolution
     * @param array<string, mixed> $metadata Additional metadata about resolution
     * @return void
     */
    public function cacheSiteSpecificTemplateResolution(
        TemplateContext $context,
        array $attemptedPaths,
        ?string $resolvedPath,
        ?string $fallbackSite = null,
        array $metadata = []
    ): void {
        // Skip caching in development mode unless plugin setting overrides it
        if ($this->shouldSkipCaching()) {
            return;
        }

        $cacheKey = $this->generateSiteSpecificTemplateKey($context, $attemptedPaths);
        
        $cacheData = [
            'resolvedPath' => $resolvedPath,
            'attemptedPaths' => $attemptedPaths,
            'fallbackSite' => $fallbackSite,
            'metadata' => $metadata,
            'timestamp' => time(),
            'elementId' => $context->element->id,
            'elementType' => get_class($context->element),
            'currentSite' => $context->element->siteId ?? Craft::$app->sites->currentSite->id,
            'baseSite' => $context->baseSite,
        ];

        // Generate site-aware cache tags for invalidation
        $tags = $this->generateSiteAwareCacheTags($context, $fallbackSite);

        Craft::$app->cache->set(
            $cacheKey,
            $cacheData,
            self::TEMPLATE_CACHE_DURATION,
            new TagDependency(['tags' => $tags])
        );
    }

    /**
     * Retrieves cached site-specific template resolution result.
     *
     * @param TemplateContext $context Template resolution context
     * @param array<string> $attemptedPaths All paths being attempted
     * @return array<string, mixed>|null Cached resolution data or null if not found
     */
    public function getCachedSiteSpecificTemplateResolution(
        TemplateContext $context,
        array $attemptedPaths
    ): ?array {
        // Skip cache in development mode unless plugin setting overrides it
        if ($this->shouldSkipCaching()) {
            return null;
        }

        $cacheKey = $this->generateSiteSpecificTemplateKey($context, $attemptedPaths);
        $cached = Craft::$app->cache->get($cacheKey);

        if ($cached === false) {
            return null;
        }

        // Validate cached data structure
        if (!is_array($cached) || !isset($cached['resolvedPath'], $cached['attemptedPaths'])) {
            return null;
        }

        return $cached;
    }

    /**
     * Invalidates cache for a specific site.
     *
     * Removes all cached data related to a specific site when site configuration changes.
     *
     * @param int|string $siteId Site ID whose cache to invalidate
     * @return void
     */
    public function invalidateSiteCache(int|string $siteId): void
    {
        $sites = Craft::$app->sites;

        if (is_numeric($siteId)) {
            $site = $sites->getSiteById((int)$siteId);
            if ($site === null) {
                // Site not found, log warning and return early
                Craft::warning("Site with ID {$siteId} not found for cache invalidation", __METHOD__);
                return;
            }
            $id = $site->id;
            $handle = $site->handle;
        } else {
            $site = $sites->getSiteByHandle((string)$siteId);
            if ($site === null) {
                // Site not found, log warning and return early
                Craft::warning("Site with handle '{$siteId}' not found for cache invalidation", __METHOD__);
                return;
            }
            $id = $site->id;
            $handle = (string)$siteId;
        }

        $tags = [
            'currentSite:' . $id,
            'fallbackSite:' . $handle,
            'site:' . $handle,
        ];
        TagDependency::invalidate(Craft::$app->cache, $tags);
    }

    /**
     * Generates cache tags for proper invalidation.
     *
     * @param TemplateContext $context Template resolution context
     * @return array<string> Array of cache tags
     */
    private function generateCacheTags(TemplateContext $context): array
    {
        $tags = [
            'bonsai_twig',
            'element:' . $context->element->id,
            'elementType:' . get_class($context->element),
        ];

        // Add current site tags
        $currentSiteId = $context->element->siteId ?? Craft::$app->sites->currentSite->id;
        $tags[] = 'currentSite:' . $currentSiteId;

        // Add context element tags if present
        if ($context->context) {
            $tags[] = 'contextElement:' . $context->context->id;
        }

        // Add site-specific tags if applicable
        if ($context->baseSite) {
            $tags[] = 'site:' . $context->baseSite;
        }

        return $tags;
    }

    /**
     * Generates site-aware cache tags for multi-site template resolution.
     *
     * @param TemplateContext $context Template resolution context
     * @param string|null $fallbackSite Fallback site used for resolution
     * @return array<string> Array of cache tags
     */
    private function generateSiteAwareCacheTags(TemplateContext $context, ?string $fallbackSite = null): array
    {
        $tags = [
            'bonsai_twig',
            'element:' . $context->element->id,
            'elementType:' . get_class($context->element),
        ];

        // Add current site tags
        $currentSiteId = $context->element->siteId ?? Craft::$app->sites->currentSite->id;
        $tags[] = 'currentSite:' . $currentSiteId;

        // Add context element tags if present
        if ($context->context) {
            $tags[] = 'contextElement:' . $context->context->id;
        }

        // Add base site tags if applicable
        if ($context->baseSite) {
            $tags[] = 'site:' . $context->baseSite;
        }

        // Add fallback site tags if used
        if ($fallbackSite && $fallbackSite !== $context->baseSite) {
            $tags[] = 'fallbackSite:' . $fallbackSite;
        }

        return $tags;
    }

    /**
     * Generates a secure cache key for site-specific template resolution.
     *
     * @param TemplateContext $context Template resolution context
     * @param array<string> $attemptedPaths All paths being attempted
     * @return string Secure cache key
     */
    private function generateSiteSpecificTemplateKey(
        TemplateContext $context,
        array $attemptedPaths
    ): string {
        $currentSiteId = $context->element->siteId ?? Craft::$app->sites->currentSite->id;
        
        $keyData = [
            'elementId' => $context->element->id,
            'elementType' => get_class($context->element),
            'path' => $context->path,
            'style' => $context->style,
            'baseSite' => $context->baseSite,
            'currentSite' => $currentSiteId,
            'attemptedPaths' => $attemptedPaths,
            'contextElementId' => $context->context?->id,
        ];

        return self::TEMPLATE_KEY_PREFIX . 'site:' . SecurityUtils::generateSecureCacheKey(
            $attemptedPaths,
            'site_template_resolution',
            $keyData
        );
    }

    /**
     * Determines whether caching should be skipped based on development mode and plugin settings.
     *
     * @return bool True if caching should be skipped, false otherwise
     */
    private function shouldSkipCaching(): bool
    {
        // DISABLED: Always skip caching to avoid multi-site cache collision issues
        return true;

        // Original logic (commented out):
        // $isDev = Craft::$app->getConfig()->general->devMode;
        //
        // if (!$isDev) {
        //     // Always cache in production mode
        //     return false;
        // }
        //
        // // In development mode, check plugin setting
        // $plugin = BonsaiTwig::getInstance();
        // if ($plugin && $plugin->getSettings()) {
        //     return !$plugin->getSettings()->cacheInDevMode;
        // }
        //
        // // Default: skip caching in dev mode if no plugin settings
        // return true;
    }
}