<?php

namespace wabisoft\bonsaitwig\services;

use craft\base\Element;

/**
 * Service class for loading template paths based on Craft elements and context.
 *
 * Simplified service that provides basic hierarchical template path resolution
 * for development use. Supports style variations and context awareness without
 * complex validation or optimization layers.
 *
 * @author Wabisoft
 * @since 6.4.0
 */
class ItemLoader
{
    /**
     * Loads and renders a template based on the provided element.
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
        // Basic parameter validation
        $entry = $variables['entry'] ?? null;
        if (!$entry instanceof Element) {
            throw new \InvalidArgumentException('Entry parameter is required and must be a valid Craft Element');
        }
        
        // Extract parameters with defaults
        $path = trim($variables['path'] ?? 'item', '/');
        $style = $variables['style'] ?? null;
        $ctx = $variables['ctx'] ?? null;
        $default = $variables['default'] ?? 'default';
        $ctxPath = trim($variables['ctxPath'] ?? 'ctx', '/');
        $baseSite = $variables['baseSite'] ?? false;
        if ($baseSite) {
            $baseSite = trim($baseSite, '/');
        }

        // Get element properties for path building
        $section = $entry->section?->handle ?? $entry->group?->handle ?? '';
        $type = $entry->type?->handle ?? false;
        $slug = $entry->slug ?? '';

        // Build template paths in order of specificity
        $checkTemplates = [];

        // Build prefixes: $baseSite/$path (site-first) and $path (global)
        $prefixes = [];
        if ($baseSite) {
            $prefixes[] = $baseSite . '/' . $path;
        }
        $prefixes[] = $path;

        // For each prefix, add paths in order of specificity
        foreach ($prefixes as $prefix) {
            // Add context-specific paths if context element exists
            if ($ctx) {
                $contextSection = $ctx->section?->handle ?? $ctx->group?->handle ?? '';
                $contextType = $ctx->type?->handle ?? '';

                if ($style && $contextSection && $contextType) {
                    $checkTemplates[] = $prefix . '/' . $section . '/' . $ctxPath . '/' . $contextSection . '/' . $contextType . '/' . $style;
                }
                if ($type && $contextSection && $contextType) {
                    $checkTemplates[] = $prefix . '/' . $section . '/' . $ctxPath . '/' . $contextSection . '/' . $contextType . '/' . $type;
                    $checkTemplates[] = $prefix . '/' . $section . '/' . $ctxPath . '/' . $contextSection . '/' . $contextType . '/' . $default;
                }
                if ($style && $contextSection) {
                    $checkTemplates[] = $prefix . '/' . $section . '/' . $ctxPath . '/' . $contextSection . '/' . $style;
                }
                if ($type && $contextSection) {
                    $checkTemplates[] = $prefix . '/' . $section . '/' . $ctxPath . '/' . $contextSection . '/' . $type;
                }
                if ($contextSection) {
                    $checkTemplates[] = $prefix . '/' . $section . '/' . $ctxPath . '/' . $contextSection . '/' . $default;
                    $checkTemplates[] = $prefix . '/' . $section . '/' . $ctxPath . '/' . $contextSection;
                }
            }

            // Add non-context template paths
            if ($style) {
                if ($type) {
                    $checkTemplates[] = $prefix . '/' . $section . '/' . $type . '/' . $style;
                }
                $checkTemplates[] = $prefix . '/' . $section . '/' . $style;
                $checkTemplates[] = $prefix . '/' . $style;
            }

            if ($type) {
                $checkTemplates[] = $prefix . '/' . $section . '/' . $type . '/' . $slug;
                $checkTemplates[] = $prefix . '/' . $section . '/' . $type;
                $checkTemplates[] = $prefix . '/' . $section . '/' . $type . '/' . $default;
            } else {
                // Category (no type): include group/slug path
                if (!empty($slug)) {
                    $checkTemplates[] = $prefix . '/' . $section . '/' . $slug;
                }
            }

            // Add general fallback paths
            $checkTemplates[] = $prefix . '/' . $section . '/' . $default;
            $checkTemplates[] = $prefix . '/' . $section;
            $checkTemplates[] = $prefix . '/' . $default;
        }

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            '',
            'item'
        );
    }
}
