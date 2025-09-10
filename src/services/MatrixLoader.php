<?php

namespace wabisoft\bonsaitwig\services;

use craft\base\Element;
use craft\elements\Entry;
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
     * Provides specialized template resolution for Craft matrix blocks, supporting
     * context-aware rendering, style variations, and hierarchical fallback patterns.
     *
     * @param array<string, mixed> $variables Configuration array containing:
     *        - block: Required. Craft Matrix Block or Entry element to base template paths on
     *        - path: Optional. Base path prefix (defaults to 'matrix')
     *        - style: Optional. Style variation name for block customization
     *        - ctx: Optional. Context Element for parent-aware rendering
     *        - ctxPath: Optional. Context path segment (defaults to 'ctx')
     *        - nextBlock: Optional. Next matrix block for context awareness
     *        - prevBlock: Optional. Previous matrix block for context awareness
     *        - parentBlock: Optional. Parent matrix block for nested hierarchies
     *        - blockIndex: Optional. Index of block within field for context
     *        - loopIndex: Optional. Current loop iteration (0-indexed) for Twig loop variable
     *        - loopLength: Optional. Total number of items in loop for Twig loop variable
     *        - variables: Optional. Additional variables to pass to the template
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
        
        // Enhanced context parameters for improved matrix handling
        $nextBlock = $validatedVars['nextBlock'] ?? null;
        $prevBlock = $validatedVars['prevBlock'] ?? null;
        $parentBlock = $validatedVars['parentBlock'] ?? null;
        $blockIndex = $validatedVars['blockIndex'] ?? null;
        
        // Loop-related parameters for Twig loop variable support
        $loopLength = $validatedVars['loopLength'] ?? null;
        $loopIndex = $validatedVars['loopIndex'] ?? null;

        // Block is already validated as MatrixBlock or Entry by InputValidator

        // Get element properties for path building
        // Handle both MatrixBlock and Entry elements for Craft 4->5 migration compatibility
        if ($block instanceof MatrixBlock) {
            $type = $block?->type?->handle;
        } elseif ($block instanceof \craft\elements\Entry) {
            $type = $block?->type?->handle;
        } else {
            $type = 'default';
        }
        
        $default = 'default';

        // Build array of possible template paths exactly like the original include statement
        $checkTemplates = [];

        // Get handle and style from variables (can be null)
        $handle = $validatedVars['handle'] ?? null;
        $style = $validatedVars['style'] ?? null;

        // Build paths in exact same order as original include statement:
        // 'matrix/handle/' ~ handle ?? null ~ '/' ~ block.type,
        if ($handle) {
            $checkTemplates[] = "{$path}/handle/{$handle}/{$type}";
        }

        // 'matrix/style/' ~ style ?? null ~ '/' ~ block.type,
        if ($style) {
            $checkTemplates[] = "{$path}/style/{$style}/{$type}";
        }

        // 'matrix/' ~ block.type,
        $checkTemplates[] = "{$path}/{$type}";
        
        // 'matrix/default'
        $checkTemplates[] = "{$path}/{$default}";

        // Add enhanced paths for additional features (after the core paths)
        
        // Add context-aware paths if context element exists
        if ($ctx) {
            $contextSection = $ctx?->section?->handle;
            $contextType = $ctx?->type?->handle;
            
            if ($contextSection && $contextType) {
                if ($style) {
                    $checkTemplates[] = "{$path}/{$ctxPath}/{$contextSection}/{$contextType}/style/{$style}/{$type}";
                }
                $checkTemplates[] = "{$path}/{$ctxPath}/{$contextSection}/{$contextType}/{$type}";
                $checkTemplates[] = "{$path}/{$ctxPath}/{$contextSection}/{$type}";
            }
        }

        // Add position-aware paths if we have block index
        if ($blockIndex !== null) {
            $isFirst = $prevBlock === null;
            $isLast = $nextBlock === null;
            
            if ($isFirst) {
                if ($style) {
                    $checkTemplates[] = "{$path}/position/first/style/{$style}/{$type}";
                }
                $checkTemplates[] = "{$path}/position/first/{$type}";
            }
            
            if ($isLast) {
                if ($style) {
                    $checkTemplates[] = "{$path}/position/last/style/{$style}/{$type}";
                }
                $checkTemplates[] = "{$path}/position/last/{$type}";
            }
            
            if (!$isFirst && !$isLast) {
                if ($style) {
                    $checkTemplates[] = "{$path}/position/middle/style/{$style}/{$type}";
                }
                $checkTemplates[] = "{$path}/position/middle/{$type}";
            }
        }

        // Add nested hierarchy paths if parent block exists
        if ($parentBlock instanceof MatrixBlock) {
            $parentType = $parentBlock?->type?->handle;
            $parentField = $parentBlock?->field?->handle;
            
            if ($parentField && $parentType) {
                if ($style) {
                    $checkTemplates[] = "{$path}/nested/{$parentField}/{$parentType}/style/{$style}/{$type}";
                }
                $checkTemplates[] = "{$path}/nested/{$parentField}/{$parentType}/{$type}";
                $checkTemplates[] = "{$path}/nested/{$parentField}/{$type}";
            }
        }

        // Build loop variable if we have the necessary information
        $loopVariable = null;
        if ($loopIndex !== null && $loopLength !== null) {
            $loopVariable = [
                'index' => $loopIndex + 1,        // 1-indexed
                'index0' => $loopIndex,           // 0-indexed
                'first' => $loopIndex === 0,
                'last' => $loopIndex === ($loopLength - 1),
                'length' => $loopLength,
                'revindex' => $loopLength - $loopIndex,      // 1-indexed from end
                'revindex0' => $loopLength - $loopIndex - 1, // 0-indexed from end
            ];
        }

        // Debug: Log template paths being checked (only in dev mode)
        if (\Craft::$app->getConfig()->general->devMode) {
            \Craft::info('MatrixLoader checking templates: ' . implode(', ', $checkTemplates), __METHOD__);
            \Craft::info('Element type: ' . get_class($block) . ', Type handle: ' . $type, __METHOD__);
        }

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
                'loop' => $loopVariable,
            ])
        );

        // Handle nested variables parameter (like ItemLoader)
        $nestedVariables = [];
        if (isset($validatedVars['variables']) && is_array($validatedVars['variables'])) {
            $nestedVariables = $validatedVars['variables'];
        }

        // Ensure the block variable is available in templates
        $templateVariables = array_merge($validatedVars, $nestedVariables, [
            'block' => $block,
            'nextBlock' => $nextBlock,
            'prevBlock' => $prevBlock,
            'parentBlock' => $parentBlock,
            'blockIndex' => $blockIndex,
            'loop' => $loopVariable,
        ]);

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $templateVariables,
            '',
            TemplateType::MATRIX,
            TemplateType::MATRIX->getAllowedDebugValues()
        );
    }
}