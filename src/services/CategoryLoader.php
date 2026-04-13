<?php

namespace wabisoft\bonsaitwig\services;

use craft\base\Element;
use wabisoft\bonsaitwig\BonsaiTwig;

/**
 * Service class for loading template paths based on Craft categories.
 *
 * Simplified service that provides basic hierarchical template path resolution
 * for development use. Focuses on core functionality without complex validation
 * or optimization layers.
 *
 * @author Wabisoft
 * @since 6.4.0
 */
class CategoryLoader
{
    /**
     * Loads and renders a template based on the provided category.
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
        // Basic parameter validation
        $category = $variables['entry'] ?? null;
        if (!$category instanceof Element) {
            throw new \InvalidArgumentException('Entry parameter is required and must be a valid Craft Element');
        }
        
        // Extract parameters with defaults
        $path = trim($variables['path'] ?? BonsaiTwig::getInstance()->getSettings()->getPathForType('category'), '/');
        $baseSite = $variables['baseSite'] ?? false;

        // Get category properties for path building
        $group = $category->group?->handle ?? '';
        $slug = $category->slug ?? '';

        // Build template paths in order of specificity
        $checkTemplates = [];

        // Build prefixes: $baseSite/$path (site-first) and $path (global)
        $prefixes = [];
        if ($baseSite) {
            $prefixes[] = $baseSite . '/' . $path;
            $primarySite = \Craft::$app->sites->primarySite->handle;
            if ($baseSite !== $primarySite) {
                $prefixes[] = $primarySite . '/' . $path;
            }
        }
        $prefixes[] = $path;

        // Add paths interleaved by specificity: all prefixes per level before dropping down
        $addPath = function (string $templatePath) use (&$checkTemplates, $prefixes) {
            foreach ($prefixes as $prefix) {
                $candidate = $prefix . '/' . $templatePath;
                if (!in_array($candidate, $checkTemplates)) {
                    $checkTemplates[] = $candidate;
                }
            }
        };

        $addPath($group . '/' . $slug);
        $addPath($group . '/default');
        $addPath($group);
        $addPath('default');

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            'category'
        );
    }
}
