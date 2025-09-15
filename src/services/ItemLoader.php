<?php

namespace wabisoft\bonsaitwig\services;

use craft\base\Element;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use wabisoft\bonsaitwig\enums\TemplateType;
use wabisoft\bonsaitwig\exceptions\InvalidElementException;
use wabisoft\bonsaitwig\utilities\InputValidator;
use wabisoft\bonsaitwig\BonsaiTwig;

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
     *        - nestByElementType: Optional. When true, nests under element type directory (e.g., item/entry or item/category). Default false.
     *        - elementPaths: Optional. False or an array mapping element types to subdirectories. Example: ['entry' => 'entry', 'category' => 'category'].
     *                         If provided as an array, this overrides nestByElementType and prefixes the base path with the mapped segment for the element kind.
     *
     * @throws \InvalidArgumentException If entry is not a valid Craft Element
     * @return string The rendered template content
     */
    public static function load(array $variables = []): string
    {
        // Validate and sanitize all input parameters
        $validatedVars = InputValidator::validateServiceParameters($variables, TemplateType::ITEM);
        
        // Extract validated parameters with defaults
        $entry = $validatedVars['entry'];
        $path = $validatedVars['path'] ?? 'item';
        $style = $validatedVars['style'] ?? null;
        $ctx = $validatedVars['ctx'] ?? null;
        $default = $validatedVars['default'] ?? 'default';
        $ctxPath = StringHelper::trim($validatedVars['ctxPath'] ?? 'ctx', '/');
        $baseSite = $validatedVars['baseSite'] ?? false;
        $nestByElementType = (bool)($validatedVars['nestByElementType'] ?? false);
        // Determine elementPaths from variables or settings; false bypasses logic
        $settings = BonsaiTwig::getInstance()?->getSettings();
        $elementPaths = $validatedVars['elementPaths'] ?? ($settings->itemsTemplateElementPaths ?? false); // false or array

        // Determine effective base path, optionally nested by element type
        $effectivePath = $path;

        // Prefer explicit elementPaths mapping when provided
        if (is_array($elementPaths)) {
            $elementKind = null;
            if ($entry instanceof \craft\elements\Category) {
                $elementKind = 'category';
            } elseif ($entry instanceof \craft\elements\Entry) {
                $elementKind = 'entry';
            }

            if ($elementKind) {
                // Support both short kind keys and FQCN keys
                $mapped = $elementPaths[$elementKind]
                    ?? ($elementKind === 'entry' ? ($elementPaths[\craft\elements\Entry::class] ?? null) : null)
                    ?? ($elementKind === 'category' ? ($elementPaths[\craft\elements\Category::class] ?? null) : null);

                if (is_string($mapped) && $mapped !== '') {
                    $effectivePath = rtrim($path, '/') . '/' . ltrim($mapped, '/');
                }
            }
        } elseif ($nestByElementType) {
            // Backward-compatible boolean flag
            if ($entry instanceof \craft\elements\Category) {
                $effectivePath = $path . '/category';
            } elseif ($entry instanceof \craft\elements\Entry) {
                $effectivePath = $path . '/entry';
            }
        }

        // Get entry properties for path building
        $section = $entry->section?->handle ?? $entry->group?->handle ?? '';
        $type = $entry->type?->handle ?? false;
        $slug = $entry->slug;

        // Build array of possible template paths matching Craft's native pattern
        $checkTemplates = [];

        // Helper to add both baseSite and default versions of a path
        $addPath = function(string $templatePath) use (&$checkTemplates, $baseSite, $effectivePath): void {
            $pathsToAdd = [];
            
            // Add base path first
            $pathsToAdd[] = $effectivePath . '/' . $templatePath;
            
            // Add site-specific path if baseSite is set (as fallback)
            if ($baseSite) {
                $pathsToAdd[] = $baseSite . '/' . $effectivePath . '/' . $templatePath;
                $pathsToAdd[] = 'default/' . $effectivePath . '/' . $templatePath;
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
                $addPath("{$section}/{$ctxPath}/{$ctx->section?->handle}/{$ctx->type?->handle}/{$style}");
            }
            if ($type) {
                $addPath("{$section}/{$ctxPath}/{$ctx->section?->handle}/{$ctx->type?->handle}/{$type}");
                $addPath("{$section}/{$ctxPath}/{$ctx->section?->handle}/{$ctx->type?->handle}/{$default}");
            }
            if ($style) {
                $addPath("{$section}/{$ctxPath}/{$ctx->section?->handle}/{$style}");
            }
            if ($type) {
                $addPath("{$section}/{$ctxPath}/{$ctx->section?->handle}/{$type}");
            }
            // Default context section fallbacks
            $addPath("{$section}/{$ctxPath}/{$ctx->section?->handle}/{$default}");
            $addPath("{$section}/{$ctxPath}/{$ctx->section?->handle}");
        }

        // Add non-context template paths
        if ($style) {
            if ($type) {
                $addPath("{$section}/{$type}/{$style}");
            }
            // Section-specific style
            $addPath("{$section}/{$style}");
            // Global style fallback (e.g., item/none)
            $addPath("{$style}");
        }
        if ($type) {
            $addPath("{$section}/{$type}/{$slug}");
            $addPath("{$section}/{$type}");
            $addPath("{$section}/{$type}/{$default}");
        } else {
            // Category (no type): include group/slug path
            if (!empty($slug)) {
                $addPath("{$section}/{$slug}");
            }
        }
        
        // Add most general fallback paths
        $addPath("{$section}/{$default}");
        $addPath($section);
        $addPath($default);

        // Use HierarchyTemplateLoader to find first matching template
        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $validatedVars,
            '',  // basePath is no longer used
            TemplateType::ITEM,
            TemplateType::ITEM->getAllowedDebugValues()
        );
    }
}
