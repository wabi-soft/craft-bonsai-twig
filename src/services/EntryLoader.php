<?php

namespace wabisoft\bonsaitwig\services;

use craft\base\Element;

/**
 * Service class for loading template paths based on Craft entries.
 *
 * Simplified service that provides basic hierarchical template path resolution
 * for development use. Focuses on core functionality without complex validation
 * or optimization layers.
 *
 * @author Wabisoft
 * @since 6.4.0
 */
class EntryLoader
{
    /**
     * Loads and renders a template based on the provided entry.
     *
     * @param array<string, mixed> $variables Configuration array containing:
     *        - entry: Required. Craft Entry element to base template paths on
     *        - path: Optional. Base path prefix (defaults to 'entry')
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
        $path = $variables['path'] ?? 'entry';
        $baseSite = $variables['baseSite'] ?? false;

        // Get entry properties for path building
        $section = $entry->section?->handle ?? '';
        $type = $entry->type?->handle ?? '';
        $slug = $entry->slug ?? '';

        // Build template paths in order of specificity
        $checkTemplates = [];

        // Build prefixes: $baseSite/entry (site-first) and entry (global)
        $prefixes = [];
        if ($baseSite) {
            $prefixes[] = $baseSite . '/' . $path;
        }
        $prefixes[] = $path;

        // For each prefix, add paths in order of specificity
        foreach ($prefixes as $prefix) {
            // Slug-specific paths
            $checkTemplates[] = $prefix . '/' . $section . '/' . $type . '/' . $slug;

            // _entry fallback after slug-specific path
            $checkTemplates[] = $prefix . '/' . $section . '/' . $type . '/_entry';

            // Section/slug direct match (for entries without type)
            $checkTemplates[] = $prefix . '/' . $section . '/' . $slug;

            // Type and section fallbacks
            $checkTemplates[] = $prefix . '/' . $section . '/' . $type;
            $checkTemplates[] = $prefix . '/' . $section . '/default';
            $checkTemplates[] = $prefix . '/' . $section;

            // Type-only fallback
            $checkTemplates[] = $prefix . '/' . $type;

            // Default fallback for this prefix
            $checkTemplates[] = $prefix . '/default';
        }

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            '',
            'entry'
        );
    }
}
