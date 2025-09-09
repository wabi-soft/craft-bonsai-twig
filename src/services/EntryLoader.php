<?php

namespace wabisoft\bonsaitwig\services;

use craft\base\Element;
use craft\helpers\ArrayHelper;
use wabisoft\bonsaitwig\enums\TemplateType;

/**
 * Service class for loading template paths based on Craft entries.
 *
 * This class provides hierarchical template path resolution by examining an entry's
 * section, type and slug to determine the most appropriate template to load.
 * It follows a fallback pattern from most specific to most general template paths.
 *
 * The resolution order follows Craft's native template hierarchy:
 * 1. [site/]entry/section/type/slug
 * 2. [site/]entry/section/slug
 * 3. [site/]entry/section/type
 * 4. [site/]entry/section/default
 * 5. [site/]entry/section
 * 6. [site/]entry/type
 * 7. [site/]entry/default
 *
 * @author Wabisoft
 * @since 6.4.0
 */
class EntryLoader
{
    /**
     * Loads and renders a template based on the provided entry and configuration.
     *
     * Generates a prioritized list of possible template paths based on the entry's
     * section, type, and slug properties. Supports multi-site template resolution
     * and provides comprehensive fallback mechanisms.
     *
     * Template path resolution follows this hierarchy:
     * - Section/type/slug combination (most specific)
     * - Section/slug combination
     * - Section/type combination
     * - Section/default combination
     * - Section only
     * - Type only
     * - Default fallback (most general)
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
        // Extract and validate the required entry element
        $entry = ArrayHelper::getValue($variables, 'entry');
        $path = (string) (ArrayHelper::getValue($variables, 'path') ?: 'entry');
        $baseSite = ArrayHelper::getValue($variables, 'baseSite') ?: false;

        if (!$entry instanceof Element) {
            throw new \InvalidArgumentException('EntryLoader::load() expects "entry" to be a valid Craft Element.');
        }

        // Get entry properties for path building
        $section = $entry->section?->handle ?? '';
        $type = $entry->type?->handle ?? '';
        $slug = $entry->slug ?? '';

        // Build array of possible template paths matching Craft's native pattern
        $checkTemplates = [];

        // Helper to add both baseSite and default versions of a path
        $addPath = function(string $templatePath) use (&$checkTemplates, $baseSite, $path): void {
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

        // Add paths in order of specificity
        $addPath($section . '/' . $type . '/' . $slug);       // [site/]entry/section/type/slug
        $addPath($section . '/' . $slug);                     // [site/]entry/section/slug
        $addPath($section . '/' . $type);                     // [site/]entry/section/type
        $addPath($section . '/default');                      // [site/]entry/section/default
        $addPath($section);                                   // [site/]entry/section
        $addPath($type);                                      // [site/]entry/type
        $addPath('default');                                  // [site/]entry/default

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            '',  // basePath is no longer used
            TemplateType::ENTRY,
            TemplateType::ENTRY->getAllowedDebugValues()
        );
    }
}
