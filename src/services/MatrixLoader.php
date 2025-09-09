<?php

namespace wabisoft\bonsaitwig\services;

use craft\base\Element;
use craft\elements\MatrixBlock;
use craft\helpers\ArrayHelper;
use wabisoft\bonsaitwig\enums\TemplateType;
use wabisoft\bonsaitwig\exceptions\InvalidElementException;
use wabisoft\bonsaitwig\utilities\InputValidator;
use wabisoft\bonsaitwig\valueobjects\TemplateContext;

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
     * @param array<string, mixed> $variables Configuration array
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
        
        // Enhanced context parameters for improved matrix handling
        $nextBlock = $validatedVars['nextBlock'] ?? null;
        $prevBlock = $validatedVars['prevBlock'] ?? null;
        $parentBlock = $validatedVars['parentBlock'] ?? null;
        $blockIndex = $validatedVars['blockIndex'] ?? null;

        // Validate that block is actually a MatrixBlock
        if (!($block instanceof MatrixBlock)) {
            throw new InvalidElementException(
                expectedType: 'MatrixBlock',
                actualValue: $block,
                message: 'Matrix template loading requires a valid MatrixBlock element'
            );
        }

        // Get block properties for path building
        $type = $block?->type?->handle;
        $default = 'default';

        // Build array of possible template paths
        $checkTemplates = [];

        // Helper to add both baseSite and default versions of a path
        $addPath = function(string $templatePath) use (&$checkTemplates, $path): void {
            $checkTemplates[] = $path . '/' . $templatePath;
        };

        // Add basic template paths
        if ($style && $style !== 'none') {
            $addPath("style/{$style}/{$type}");
        }
        $addPath($type);
        $addPath($default);

        // Create template context
        $templateContext = new TemplateContext(
            element: $block,
            path: $path,
            style: $style,
            context: $ctx,
            variables: array_merge($validatedVars, [
                'nextBlock' => $nextBlock,
                'prevBlock' => $prevBlock,
                'parentBlock' => $parentBlock,
                'blockIndex' => $blockIndex,
            ])
        );

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $validatedVars,
            '',
            TemplateType::MATRIX,
            TemplateType::MATRIX->getAllowedDebugValues()
        );
    }
}