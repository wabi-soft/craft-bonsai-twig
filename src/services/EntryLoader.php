<?php

namespace wabisoft\bonsaitwig\services;

use craft\helpers\ArrayHelper;
use craft\base\Element;

/**
 * Service class for loading template paths based on Craft entries.
 * 
 * This class provides hierarchical template path resolution by examining an entry's
 * section, type and slug to determine the most appropriate template to load.
 * It follows a fallback pattern from most specific to most general template paths.
 */
class EntryLoader
{
    /**
     * Loads a template path based on the provided entry and configuration.
     * 
     * Generates a prioritized list of possible template paths based on:
     * - Section/type/slug combination
     * - Section/type combination
     * - Section only
     * - Default fallback
     *
     * @param array $variables Configuration array containing:
     *        - entry: Required. Craft Entry element to base template paths on
     *        - path: Optional. Base path prefix (defaults to 'entry')
     *        - baseSite: Optional. Base site handle (defaults to false)
     * 
     * @throws \InvalidArgumentException If entry is not a valid Craft Element
     * @return string The resolved template path
     */
    public static function load(array $variables = []): string
    {
        // Extract and validate the required entry element
        $entry = ArrayHelper::getValue($variables, 'entry');
        $path = ArrayHelper::getValue($variables, 'path') ?: 'entry';
        $baseSite = ArrayHelper::getValue($variables, 'baseSite') ?: false;

        if (!$entry instanceof Element) {
            throw new \InvalidArgumentException('EntryLoader::load() expects "entry" to be a valid Craft Element.');
        }

        // Get entry properties for path building
        $section = $entry->section->handle ?? '';
        $type = $entry->type->handle ?? '';
        $slug = $entry->slug ?? '';

        // Build array of possible template paths matching Craft's native pattern
        $checkTemplates = [];

        // Helper to add both baseSite and default versions of a path
        $addPath = function($templatePath) use (&$checkTemplates, $baseSite, $path) {
            if ($baseSite) {
                $checkTemplates[] = $baseSite . '/' . $path . '/' . $templatePath;
                $checkTemplates[] = 'default/' . $path . '/' . $templatePath;
            }
            $checkTemplates[] = $path . '/' . $templatePath;
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
            'entry',
            ['entry', 'all']
        );
    }
}
