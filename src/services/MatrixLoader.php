<?php

namespace wabisoft\bonsaitwig\services;

use craft\base\Element;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use wabisoft\bonsaitwig\BonsaiTwig;

/**
 * Service class for loading template paths based on Craft matrix blocks.
 *
 * Simplified service that provides basic hierarchical template path resolution
 * for development use. Maintains essential matrix template hierarchy without
 * complex validation or optimization layers.
 *
 * @author Wabisoft
 * @since 6.4.0
 */
class MatrixLoader
{
    /**
     * Loads and renders a template based on the provided matrix block.
     *
     * @param array<string, mixed> $variables Configuration array containing:
     *        - block: Required. Craft Matrix Block or Entry element to base template paths on
     *        - path: Optional. Base path prefix (defaults to 'matrix')
     *        - style: Optional. Style variation name for block customization
     *        - handle: Optional. Handle variation for template paths
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
        // Basic parameter validation
        $block = $variables['block'] ?? null;
        if (!$block instanceof MatrixBlock && !$block instanceof Entry) {
            throw new \InvalidArgumentException('Block parameter is required and must be a valid MatrixBlock or Entry element');
        }
        
        // Extract parameters with defaults
        $path = trim($variables['path'] ?? BonsaiTwig::getInstance()->getSettings()->getPathForType('matrix'), '/');
        $style = $variables['style'] ?? null;
        $handle = $variables['handle'] ?? null;
        $ctx = $variables['ctx'] ?? null;
        $ctxPath = $variables['ctxPath'] ?? 'ctx';
        
        // Context parameters for matrix handling
        $nextBlock = $variables['nextBlock'] ?? null;
        $prevBlock = $variables['prevBlock'] ?? null;
        $parentBlock = $variables['parentBlock'] ?? null;
        $blockIndex = $variables['blockIndex'] ?? null;
        
        // Loop-related parameters for Twig loop variable support
        $loopLength = $variables['loopLength'] ?? null;
        $loopIndex = $variables['loopIndex'] ?? null;

        // Get element properties for path building
        if ($block instanceof MatrixBlock) {
            $type = $block->type?->handle ?? 'default';
        } elseif ($block instanceof Entry) {
            $type = $block->type?->handle ?? 'default';
        } else {
            $type = 'default';
        }

        // Build template paths in order of specificity (matching original include statement)
        $checkTemplates = [];

        // Core paths in exact same order as original include statement
        if ($handle) {
            $checkTemplates[] = $path . '/handle/' . $handle . '/' . $type;
        }

        if ($style) {
            $checkTemplates[] = $path . '/style/' . $style . '/' . $type;
        }

        $checkTemplates[] = $path . '/' . $type;
        $checkTemplates[] = $path . '/default';

        // Add context-aware paths if context element exists
        if ($ctx) {
            $contextSection = $ctx->section?->handle ?? $ctx->group?->handle ?? '';
            $contextType = $ctx->type?->handle ?? '';
            
            if ($contextSection && $contextType) {
                if ($style) {
                    $checkTemplates[] = $path . '/' . $ctxPath . '/' . $contextSection . '/' . $contextType . '/style/' . $style . '/' . $type;
                }
                $checkTemplates[] = $path . '/' . $ctxPath . '/' . $contextSection . '/' . $contextType . '/' . $type;
                $checkTemplates[] = $path . '/' . $ctxPath . '/' . $contextSection . '/' . $type;
            }
        }

        // Add position-aware paths if we have block index
        if ($blockIndex !== null) {
            $isFirst = $prevBlock === null;
            $isLast = $nextBlock === null;
            
            if ($isFirst) {
                if ($style) {
                    $checkTemplates[] = $path . '/position/first/style/' . $style . '/' . $type;
                }
                $checkTemplates[] = $path . '/position/first/' . $type;
            }
            
            if ($isLast) {
                if ($style) {
                    $checkTemplates[] = $path . '/position/last/style/' . $style . '/' . $type;
                }
                $checkTemplates[] = $path . '/position/last/' . $type;
            }
            
            if (!$isFirst && !$isLast) {
                if ($style) {
                    $checkTemplates[] = $path . '/position/middle/style/' . $style . '/' . $type;
                }
                $checkTemplates[] = $path . '/position/middle/' . $type;
            }
        }

        // Add nested hierarchy paths if parent block exists
        if ($parentBlock instanceof MatrixBlock) {
            $parentType = $parentBlock->type?->handle ?? '';
            $parentField = $parentBlock->field?->handle ?? '';
            
            if ($parentField && $parentType) {
                if ($style) {
                    $checkTemplates[] = $path . '/nested/' . $parentField . '/' . $parentType . '/style/' . $style . '/' . $type;
                }
                $checkTemplates[] = $path . '/nested/' . $parentField . '/' . $parentType . '/' . $type;
                $checkTemplates[] = $path . '/nested/' . $parentField . '/' . $type;
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

        // Handle nested variables parameter
        $nestedVariables = [];
        if (isset($variables['variables']) && is_array($variables['variables'])) {
            $nestedVariables = $variables['variables'];
        }

        // Prepare template variables
        $templateVariables = array_merge($variables, $nestedVariables, [
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
            'matrix'
        );
    }
}
