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
     * 
     * @throws \InvalidArgumentException If entry is not a valid Craft Element
     * @return string The resolved template path
     */
    public static function load(array $variables = []): string
    {
        // Extract and validate the required category element
        $category = ArrayHelper::getValue($variables, 'entry');
        $path = ArrayHelper::getValue($variables, 'path') ?: 'category';

        if (!$category instanceof Element) {
            throw new \InvalidArgumentException('CategoryLoader::load() expects "entry" to be a valid Craft Element.');
        }

        // Get category properties for path building
        $group = $category->group->handle ?? '';
        $slug = $category->slug ?? '';
        $default = 'default';

        // Build array of possible template paths in order of specificity
        $checkTemplates = [
            $group . '/' . $slug,      // Most specific: group/slug
            $group . '/' . $default,   // group/default
            $group,                    // group only
            $default,                  // least specific: default
        ];

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            $path,
            'category',
            'showCategoryPath',
            'showCategoryHierarchy'
        );
    }
}
