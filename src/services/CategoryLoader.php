<?php

namespace wabisoft\bonsaitwig\services;

use craft\base\Element;
use craft\helpers\ArrayHelper;
use wabisoft\bonsaitwig\enums\TemplateType;

/**
 * Service class for loading template paths based on Craft categories.
 *
 * This class provides hierarchical template path resolution by examining a category's
 * group and slug to determine the most appropriate template to load.
 * It follows a fallback pattern from most specific to most general template paths.
 *
 * The resolution order follows this hierarchy:
 * 1. [site/]category/group/slug
 * 2. [site/]category/group/default
 * 3. [site/]category/group
 * 4. [site/]category/default
 *
 * @author Wabisoft
 * @since 6.4.0
 */
class CategoryLoader
{
    /**
     * Loads and renders a template based on the provided category and configuration.
     *
     * Generates a prioritized list of possible template paths based on the category's
     * group and slug properties. Supports multi-site template resolution and provides
     * comprehensive fallback mechanisms for category-specific templates.
     *
     * Template path resolution follows this hierarchy:
     * - Group/slug combination (most specific)
     * - Group/default combination
     * - Group only
     * - Default fallback (most general)
     *
     * @param array<string, mixed> $variables Configuration array containing:
     *        - entry: Required. Craft Category element to base template paths on
     *        - path: Optional. Base path prefix (defaults to 'category')
     *        - baseSite: Optional. Base site handle for multi-site support (defaults to false)
     *
     * @throws \InvalidArgumentException If entry is not a valid Craft Element
     * @return string The rendered template content
     */
    public static function load(array $variables = []): string
    {
        // Extract and validate the required category element
        $category = ArrayHelper::getValue($variables, 'entry');
        $path = (string) (ArrayHelper::getValue($variables, 'path') ?: 'category');
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
        $addPath("{$group}/{$slug}");       // [site/]category/group/slug
        $addPath("{$group}/{$default}");    // [site/]category/group/default
        $addPath($group);                   // [site/]category/group
        $addPath($default);                 // [site/]category/default

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            '',  // No base path needed since we include it in template paths
            TemplateType::CATEGORY,
            TemplateType::CATEGORY->getAllowedDebugValues()
        );
    }
}
