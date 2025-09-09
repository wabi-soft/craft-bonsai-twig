<?php

namespace wabisoft\bonsaitwig\services;

use craft\base\Element;
use craft\helpers\ArrayHelper;
use wabisoft\bonsaitwig\enums\TemplateType;
use wabisoft\bonsaitwig\exceptions\InvalidElementException;
use wabisoft\bonsaitwig\utilities\InputValidator;

/**
 * Service class for loading template paths based on Craft matrix blocks.
 *
 * This class provides hierarchical template path resolution by examining a matrix block's
 * type, style, and context to determine the most appropriate template to load.
 * It follows a fallback pattern from most specific to most general template paths.
 *
 * Matrix blocks have unique requirements including style support, context awareness,
 * and the ability to render within different parent element contexts.
 *
 * @author Wabisoft
 * @since 6.4.0
 */
class MatrixLoader
{
    /**
     * Loads and renders a template based on the provided matrix block and configuration.
     *
     * Provides specialized template resolution for Craft matrix blocks, supporting
     * context-aware rendering, style variations, and hierarchical fallback patterns.
     * Matrix blocks can be rendered differently based on their parent context.
     *
     * Template path resolution includes:
     * - Context-specific paths with style support
     * - Context-specific paths without style
     * - Style-specific templates (when style != 'none')
     * - Block type templates
     * - Default fallback
     *
     * @param array<string, mixed> $variables Configuration array containing:
     *        - block: Required. Craft Matrix Block element to base template paths on
     *        - path: Optional. Base path prefix (defaults to 'matrix')
     *        - style: Optional. Style variation name for block customization
     *        - ctx: Optional. Context Element for parent-aware rendering
     *        - ctxPath: Optional. Context path segment (defaults to 'ctx')
     *
     * @throws \InvalidArgumentException If block is not a valid Craft Element
     * @return string The rendered template content
     */
    public static function load(array $variables = []): string
    {
        // Validate and sanitize all input parameters
        $validatedVars = InputValidator::validateServiceParameters($variables, TemplateType::MATRIX);
        
        // Extract validated parameters with defaults
        $block = $validatedVars['block'];
        $path = $validatedVars['path'] ?? 'matrix';
        $style = $validatedVars['style'] ?? null;
        $ctx = $validatedVars['ctx'] ?? null;
        $ctxPath = $validatedVars['ctxPath'] ?? 'ctx';

        // Get block properties for path building
        $type = $block?->type?->handle;
        $default = 'default';

        // Build array of possible template paths
        $checkTemplates = [];

        // Helper to add both baseSite and default versions of a path
        $addPath = function(string $templatePath) use (&$checkTemplates, $path): void {
            // Add base path first
            $checkTemplates[] = $path . '/' . $templatePath;
        };

        // Add context-specific paths if context is provided
        if ($ctx) {
            $ctxPath = "{$ctxPath}/{$ctx?->section?->handle}/{$ctx?->type?->handle}";

            if ($style) {
                $addPath("{$ctxPath}/style/{$style}/{$type}");
            }
            $addPath("{$ctxPath}/{$type}");
            $addPath("{$ctxPath}/{$default}");
        }

        if ($style && $style != 'none') {
            $addPath("style/{$style}/{$type}");
        }

        // Add default templates as final fallback
        $addPath($type);
        $addPath($default);

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $validatedVars,
            '',  // No base path needed since we include it in template paths
            TemplateType::MATRIX,
            TemplateType::MATRIX->getAllowedDebugValues()
        );
    }
}
