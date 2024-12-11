<?php

namespace wabisoft\bonsaitwig\services;

use craft\helpers\ArrayHelper;
use craft\base\Element;

/**
 * Service class for loading template paths based on Craft categories.
 * 
 * This class provides hierarchical template path resolution by examining a category's
 * group and slug to determine the most appropriate template to load.
 * It follows a fallback pattern from most specific to most general template paths.
 */
class CategoryLoader
{
    /**
     * Loads a template path based on the provided category and configuration.
     * 
     * Generates a prioritized list of possible template paths based on:
     * - Group/slug combination
     * - Group/default combination
     * - Group only
     * - Default fallback
     *
     * @param array $variables Configuration array containing:
     *        - entry: Required. Craft Category element to base template paths on
     *        - path: Optional. Base path prefix (defaults to 'category')
     *        - baseSite: Optional. Base site handle (defaults to false)
     * 
     * @throws \InvalidArgumentException If entry is not a valid Craft Element
     * @return string The resolved template path
     */
    public static function load(array $variables = []): string
    {
        // Extract and validate the required category element
        $category = ArrayHelper::getValue($variables, 'entry');
        $path = ArrayHelper::getValue($variables, 'path') ?: 'category';
        $baseSite = ArrayHelper::getValue($variables, 'baseSite') ?: false;

        if (!$category instanceof Element) {
            throw new \InvalidArgumentException('CategoryLoader::load() expects "entry" to be a valid Craft Element.');
        }

        // Get category properties for path building
        $group = $category->group?->handle ?? '';
        $slug = $category->slug ?? '';
        $default = 'default';

        // Build array of possible template paths matching Craft's native pattern
        $checkTemplates = [];

        // Helper to add both baseSite and default versions of a path
        $addPath = function($templatePath) use (&$checkTemplates, $baseSite, $path) {
            $pathsToAdd = [];
            
            // Add site-specific path if baseSite is set
            if ($baseSite) {
                $pathsToAdd[] = $baseSite . '/' . $path . '/' . $templatePath;
                $pathsToAdd[] = 'default/' . $path . '/' . $templatePath;
            }
            
            // Add base path
            $pathsToAdd[] = $path . '/' . $templatePath;
            
            // Add only unique paths
            foreach ($pathsToAdd as $p) {
                if (!in_array($p, $checkTemplates)) {
                    $checkTemplates[] = $p;
                }
            }
        };

        // Add paths in order of specificity
        $addPath("{$group}/{$slug}");       // [site/]category/group/slug
        $addPath("{$group}/{$default}");    // [site/]category/group/default
        $addPath($group);                   // [site/]category/group
        $addPath($default);                 // [site/]category/default

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            '',  // No base path needed since we include it in template paths
            'category',
            ['category', 'all']
        );
    }
}
