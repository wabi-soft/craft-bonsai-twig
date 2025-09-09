<?php

namespace wabisoft\bonsaitwig\services;

use craft\base\Element;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;

/**
 * Service class for loading template paths based on Craft elements and context.
 *
 * This class provides hierarchical template path resolution by examining an element's
 * section, type, style and context to determine the most appropriate template to load.
 * It follows a fallback pattern from most specific to most general template paths.
 *
 * This is the most flexible loader, supporting context-aware template resolution,
 * style variations, and complex hierarchical patterns. It can handle both entries
 * and categories through their common Element interface.
 *
 * @author Wabisoft
 * @since 6.4.0
 */
class ItemLoader
{
    /**
     * Loads and renders a template based on the provided element and context parameters.
     *
     * This is the most sophisticated template loader, supporting context-aware resolution,
     * style variations, and complex hierarchical patterns. It can work with any Craft
     * element type and provides extensive customization options.
     *
     * Template path resolution includes:
     * - Context-specific paths (when ctx parameter provided)
     * - Style-specific variations (when style parameter provided)
     * - Element section and type combinations
     * - Slug-specific templates
     * - Comprehensive fallback mechanisms
     *
     * @param array<string, mixed> $variables Configuration array containing:
     *        - entry: Required. Craft Element to base template paths on
     *        - path: Optional. Base path prefix (defaults to 'item')
     *        - style: Optional. Style variation name for template customization
     *        - ctx: Optional. Context Element for additional path variations
     *        - default: Optional. Default template name (defaults to 'default')
     *        - ctxPath: Optional. Context path segment (defaults to 'ctx')
     *        - baseSite: Optional. Base site handle for multi-site support (defaults to false)
     *
     * @throws \InvalidArgumentException If entry is not a valid Craft Element
     * @return string The rendered template content
     */
    public static function load(array $variables = []): string
    {
        // Extract and validate the required entry element
        $entry = ArrayHelper::getValue($variables, 'entry');
        if (!$entry instanceof Element) {
            throw new \InvalidArgumentException('ItemLoader::load() expects "entry" to be a valid Craft Element.');
        }

        // Extract optional configuration values with defaults
        $path = $variables['path'] ?? 'item';
        $style = $variables['style'] ?? null;
        $ctx = $variables['ctx'] ?? null;
        $default = $variables['default'] ?? 'default';
        $ctxPath = StringHelper::trim($variables['ctxPath'] ?? 'ctx', '/');
        $baseSite = ArrayHelper::getValue($variables, 'baseSite') ?: false;

        // Get entry properties for path building
        $section = $entry->section?->handle ?? $entry->group?->handle ?? '';
        $type = $entry->type?->handle ?? false;
        $slug = $entry->slug;

        // Build array of possible template paths matching Craft's native pattern
        $checkTemplates = [];

        // Helper to add both baseSite and default versions of a path
        $addPath = function($templatePath) use (&$checkTemplates, $baseSite, $path) {
            $pathsToAdd = [];
            
            // Add base path first
            $pathsToAdd[] = $path . '/' . $templatePath;
            
            // Add site-specific path if baseSite is set (as fallback)
            if ($baseSite) {
                $pathsToAdd[] = $baseSite . '/' . $path . '/' . $templatePath;
                $pathsToAdd[] = 'default/' . $path . '/' . $templatePath;
            }
            
            // Add only unique paths
            foreach ($pathsToAdd as $p) {
                if (!in_array($p, $checkTemplates)) {
                    $checkTemplates[] = $p;
                }
            }
        };

        // Add context-specific paths
        if ($ctx) {
            if ($style) {
                $addPath("{$section}/{$ctxPath}/{$ctx->section->handle}/{$ctx->type->handle}/{$style}");
            }
            if ($type) {
                $addPath("{$section}/{$ctxPath}/{$ctx->section->handle}/{$ctx->type->handle}/{$type}");
                $addPath("{$section}/{$ctxPath}/{$ctx->section->handle}/{$ctx->type->handle}/{$default}");
            }
            if ($style) {
                $addPath("{$section}/{$ctxPath}/{$ctx->section->handle}/{$style}");
            }
            if ($type) {
                $addPath("{$section}/{$ctxPath}/{$ctx->section->handle}/{$type}");
            }
            // Default context section fallbacks
            $addPath("{$section}/{$ctxPath}/{$ctx->section->handle}/{$default}");
            $addPath("{$section}/{$ctxPath}/{$ctx->section->handle}");
        }

        // Add non-context template paths
        if ($style) {
            if ($type) {
                $addPath("{$section}/{$type}/{$style}");
            }
            $addPath("{$section}/{$style}");
        }
        if ($type) {
            $addPath("{$section}/{$type}/{$slug}");
            $addPath("{$section}/{$type}");
            $addPath("{$section}/{$type}/{$default}");
        }
        
        // Add most general fallback paths
        $addPath("{$section}/{$default}");
        $addPath($section);
        $addPath($default);

        // Use HierarchyTemplateLoader to find first matching template
        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            '',  // basePath is no longer used
            'item',
            ['item', 'all']
        );
    }
}
