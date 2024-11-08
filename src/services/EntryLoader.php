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
     * 
     * @throws \InvalidArgumentException If entry is not a valid Craft Element
     * @return string The resolved template path
     */
    public static function load(array $variables = []): string
    {
        // Extract and validate the required entry element
        $entry = ArrayHelper::getValue($variables, 'entry');
        $path = ArrayHelper::getValue($variables, 'path') ?: 'entry';

        if (!$entry instanceof Element) {
            throw new \InvalidArgumentException('EntryLoader::load() expects "entry" to be a valid Craft Element.');
        }

        // Get entry properties for path building
        $section = $entry->section->handle ?? '';
        $type = $entry->type->handle ?? '';
        $slug = $entry->slug ?? '';
        $default = 'default';

        // Build array of possible template paths in order of specificity
        $checkTemplates = [
            $section . '/' . $type . '/' . $slug,  // Most specific: section/type/slug
            $section . '/' . $slug,                // section/slug
            $section . '/' . $type,                // section/type
            $section . '/' . $default,             // section/default
            $section,                              // section only
            $type,                                 // type only
            $default,                              // least specific: default
        ];

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            $path,
            'entry',
            'showEntryPath',
            'showEntryHierarchy',
            'showEntryInfo'
        );
    }
}
