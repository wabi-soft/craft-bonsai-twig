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
     * Provides specialized template resolution for Craft matrix blocks, supporting
     * context-aware rendering, style variations, and hierarchical fallback patterns.
     * Matrix blocks can be rendered differently based on their parent context.
     *
     * Enhanced features include:
     * - Nested matrix block hierarchy support
     * - Next/previous block context resolution
     * - Enhanced context parameter handling
     * - Style-specific template resolution
     * - Legacy template path support
     *
     * Template path resolution includes:
     * - Nested context-specific paths with hierarchy support
     * - Context-specific paths with style support
     * - Context-specific paths without style
     * - Style-specific templates (when style != 'none')
     * - Block type templates with context awareness
     * - Default fallback with hierarchy context
     *
     * @param array<string, mixed> $variables Configuration array containing:
     *        - block: Required. Craft Matrix Block element to base template paths on
     *        - path: Optional. Base path prefix (defaults to 'matrix')
     *        - style: Optional. Style variation name for block customization
     *        - ctx: Optional. Context Element for parent-aware rendering
     *        - ctxPath: Optional. Context path segment (defaults to 'ctx')
     *        - nextBlock: Optional. Next matrix block for context awareness
     *        - prevBlock: Optional. Previous matrix block for context awareness
     *        - parentBlock: Optional. Parent matrix block for nested hierarchies
     *        - blockIndex: Optional. Index of block within field for context
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

        // Build enhanced context information for template resolution
        $matrixContext = self::buildMatrixContext($block, $ctx, $nextBlock, $prevBlock, $parentBlock, $blockIndex);

        // Build array of possible template paths with enhanced context awareness
        $checkTemplates = [];

        // Helper to add both baseSite and default versions of a path
        $addPath = function(string $templatePath) use (&$checkTemplates, $path): void {
            // Add base path first
            $checkTemplates[] = $path . '/' . $templatePath;
        };

        // Add nested hierarchy paths if parent block exists
        if ($parentBlock instanceof MatrixBlock) {
            $nestedPaths = self::generateNestedHierarchyPaths($block, $parentBlock, $style, $type, $default);
            foreach ($nestedPaths as $nestedPath) {
                $addPath($nestedPath);
            }
        }

        // Add context-aware paths with enhanced next/previous support
        if ($ctx) {
            $contextPaths = self::generateContextAwarePaths($ctx, $ctxPath, $style, $type, $default, $matrixContext);
            foreach ($contextPaths as $contextPath) {
                $addPath($contextPath);
            }
        }

        // Add block position-aware paths (first, last, middle)
        if ($blockIndex !== null) {
            $positionPaths = self::generatePositionAwarePaths($type, $style, $default, $blockIndex, $matrixContext);
            foreach ($positionPaths as $positionPath) {
                $addPath($positionPath);
            }
        }

        // Add enhanced style-specific paths with conditional logic
        if ($style && $style != 'none') {
            $styleSpecificPaths = self::generateStyleSpecificPaths($type, $style, $default, $matrixContext, $ctx);
            foreach ($styleSpecificPaths as $stylePath) {
                $addPath($stylePath);
            }
        }

        // Add conditional template paths based on block context
        $conditionalPaths = self::generateConditionalPaths($block, $type, $style, $default, $matrixContext);
        foreach ($conditionalPaths as $conditionalPath) {
            $addPath($conditionalPath);
        }

        // Add legacy template paths for renamed block types
        $legacyPaths = self::generateLegacyPaths($type, $style, $default, $matrixContext);
        foreach ($legacyPaths as $legacyPath) {
            $addPath($legacyPath);
        }

        // Add default templates as final fallback
        $addPath($type);
        $addPath($default);

        // Create enhanced template context for better debugging and caching
        $templateContext = new TemplateContext(
            element: $block,
            path: $path,
            style: $style,
            context: $ctx,
            variables: array_merge($validatedVars, [
                'matrixContext' => $matrixContext,
                'nextBlock' => $nextBlock,
                'prevBlock' => $prevBlock,
                'parentBlock' => $parentBlock,
                'blockIndex' => $blockIndex,
            ])
        );

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $validatedVars,
            '',  // No base path needed since we include it in template paths
            TemplateType::MATRIX,
            TemplateType::MATRIX->getAllowedDebugValues()
        );
    }
}

    /**
     * Builds enhanced matrix context information for template resolution.
     *
     * This method creates a comprehensive context object that includes information
     * about the matrix block's position, relationships, and hierarchy for use in
     * template path generation and conditional logic.
     *
     * @param MatrixBlock $block The current matrix block
     * @param Element|null $ctx Optional context element
     * @param MatrixBlock|null $nextBlock Next block in sequence
     * @param MatrixBlock|null $prevBlock Previous block in sequence
     * @param MatrixBlock|null $parentBlock Parent block for nested matrices
     * @param int|null $blockIndex Index position within the field
     * @return array<string, mixed> Enhanced context information
     */
    private static function buildMatrixContext(
        MatrixBlock $block,
        ?Element $ctx = null,
        ?MatrixBlock $nextBlock = null,
        ?MatrixBlock $prevBlock = null,
        ?MatrixBlock $parentBlock = null,
        ?int $blockIndex = null
    ): array {
        $context = [
            'blockId' => $block->id,
            'blockType' => $block?->type?->handle,
            'fieldHandle' => $block?->field?->handle,
            'ownerId' => $block->ownerId,
            'sortOrder' => $block->sortOrder,
        ];

        // Add position information
        $context['position'] = [
            'isFirst' => $prevBlock === null,
            'isLast' => $nextBlock === null,
            'hasNext' => $nextBlock !== null,
            'hasPrev' => $prevBlock !== null,
            'index' => $blockIndex,
        ];

        // Add next/previous block information
        if ($nextBlock) {
            $context['nextBlock'] = [
                'id' => $nextBlock->id,
                'type' => $nextBlock?->type?->handle,
                'sortOrder' => $nextBlock->sortOrder,
            ];
        }

        if ($prevBlock) {
            $context['prevBlock'] = [
                'id' => $prevBlock->id,
                'type' => $prevBlock?->type?->handle,
                'sortOrder' => $prevBlock->sortOrder,
            ];
        }

        // Add parent block information for nested matrices
        if ($parentBlock) {
            $context['parentBlock'] = [
                'id' => $parentBlock->id,
                'type' => $parentBlock?->type?->handle,
                'fieldHandle' => $parentBlock?->field?->handle,
                'sortOrder' => $parentBlock->sortOrder,
            ];
            
            // Build hierarchy chain
            $context['hierarchy'] = self::buildHierarchyChain($parentBlock);
        }

        // Add context element information
        if ($ctx) {
            $context['contextElement'] = [
                'id' => $ctx->id,
                'type' => $ctx::class,
                'section' => $ctx?->section?->handle ?? null,
                'elementType' => $ctx?->type?->handle ?? null,
            ];
        }

        return $context;
    }

    /**
     * Builds a hierarchy chain for nested matrix blocks.
     *
     * This method recursively builds a chain of parent blocks to support
     * deeply nested matrix structures and proper template path resolution.
     *
     * @param MatrixBlock $block The block to build hierarchy for
     * @param array<array<string, mixed>> $chain Current hierarchy chain
     * @return array<array<string, mixed>> Complete hierarchy chain
     */
    private static function buildHierarchyChain(MatrixBlock $block, array $chain = []): array
    {
        $blockInfo = [
            'id' => $block->id,
            'type' => $block?->type?->handle,
            'fieldHandle' => $block?->field?->handle,
            'sortOrder' => $block->sortOrder,
        ];

        array_unshift($chain, $blockInfo);

        // Check if this block has a parent (nested matrix)
        $owner = $block->getOwner();
        if ($owner instanceof MatrixBlock) {
            return self::buildHierarchyChain($owner, $chain);
        }

        return $chain;
    }

    /**
     * Generates template paths for nested matrix block hierarchies.
     *
     * This method creates template paths that reflect the nested structure
     * of matrix blocks, allowing for context-specific templates based on
     * the parent-child relationships.
     *
     * @param MatrixBlock $block Current matrix block
     * @param MatrixBlock $parentBlock Parent matrix block
     * @param string|null $style Style parameter
     * @param string $type Block type handle
     * @param string $default Default template name
     * @return array<string> Array of nested hierarchy template paths
     */
    private static function generateNestedHierarchyPaths(
        MatrixBlock $block,
        MatrixBlock $parentBlock,
        ?string $style,
        string $type,
        string $default
    ): array {
        $paths = [];
        $parentType = $parentBlock?->type?->handle;
        $parentField = $parentBlock?->field?->handle;

        // Build hierarchy path segments
        $hierarchySegments = [];
        
        // Add parent field and type
        if ($parentField) {
            $hierarchySegments[] = "field/{$parentField}";
        }
        if ($parentType) {
            $hierarchySegments[] = "parent/{$parentType}";
        }

        $hierarchyPath = implode('/', $hierarchySegments);

        if (!empty($hierarchyPath)) {
            // Add nested paths with style support
            if ($style && $style !== 'none') {
                $paths[] = "{$hierarchyPath}/style/{$style}/{$type}";
            }
            $paths[] = "{$hierarchyPath}/{$type}";
            $paths[] = "{$hierarchyPath}/{$default}";

            // Add field-specific paths without parent type
            if ($parentField) {
                if ($style && $style !== 'none') {
                    $paths[] = "field/{$parentField}/style/{$style}/{$type}";
                }
                $paths[] = "field/{$parentField}/{$type}";
                $paths[] = "field/{$parentField}/{$default}";
            }
        }

        return $paths;
    }

    /**
     * Generates context-aware template paths with enhanced next/previous support.
     *
     * This method creates template paths that are aware of the block's context
     * including its position relative to other blocks and its relationship
     * to the parent element.
     *
     * @param Element $ctx Context element
     * @param string $ctxPath Context path segment
     * @param string|null $style Style parameter
     * @param string $type Block type handle
     * @param string $default Default template name
     * @param array<string, mixed> $matrixContext Enhanced matrix context
     * @return array<string> Array of context-aware template paths
     */
    private static function generateContextAwarePaths(
        Element $ctx,
        string $ctxPath,
        ?string $style,
        string $type,
        string $default,
        array $matrixContext
    ): array {
        $paths = [];
        $basePath = "{$ctxPath}/{$ctx?->section?->handle}/{$ctx?->type?->handle}";

        // Add position-aware context paths
        $position = $matrixContext['position'];
        
        if ($position['isFirst']) {
            if ($style && $style !== 'none') {
                $paths[] = "{$basePath}/first/style/{$style}/{$type}";
            }
            $paths[] = "{$basePath}/first/{$type}";
            $paths[] = "{$basePath}/first/{$default}";
        }

        if ($position['isLast']) {
            if ($style && $style !== 'none') {
                $paths[] = "{$basePath}/last/style/{$style}/{$type}";
            }
            $paths[] = "{$basePath}/last/{$type}";
            $paths[] = "{$basePath}/last/{$default}";
        }

        if (!$position['isFirst'] && !$position['isLast']) {
            if ($style && $style !== 'none') {
                $paths[] = "{$basePath}/middle/style/{$style}/{$type}";
            }
            $paths[] = "{$basePath}/middle/{$type}";
            $paths[] = "{$basePath}/middle/{$default}";
        }

        // Add next/previous context paths
        if (isset($matrixContext['nextBlock'])) {
            $nextType = $matrixContext['nextBlock']['type'];
            if ($style && $style !== 'none') {
                $paths[] = "{$basePath}/before/{$nextType}/style/{$style}/{$type}";
            }
            $paths[] = "{$basePath}/before/{$nextType}/{$type}";
        }

        if (isset($matrixContext['prevBlock'])) {
            $prevType = $matrixContext['prevBlock']['type'];
            if ($style && $style !== 'none') {
                $paths[] = "{$basePath}/after/{$prevType}/style/{$style}/{$type}";
            }
            $paths[] = "{$basePath}/after/{$prevType}/{$type}";
        }

        // Add standard context paths
        if ($style && $style !== 'none') {
            $paths[] = "{$basePath}/style/{$style}/{$type}";
        }
        $paths[] = "{$basePath}/{$type}";
        $paths[] = "{$basePath}/{$default}";

        return $paths;
    }

    /**
     * Generates position-aware template paths based on block index and context.
     *
     * This method creates template paths that reflect the block's position
     * within its field, allowing for different templates based on whether
     * the block is first, last, or in the middle of a sequence.
     *
     * @param string $type Block type handle
     * @param string|null $style Style parameter
     * @param string $default Default template name
     * @param int $blockIndex Index of the block within its field
     * @param array<string, mixed> $matrixContext Enhanced matrix context
     * @return array<string> Array of position-aware template paths
     */
    private static function generatePositionAwarePaths(
        string $type,
        ?string $style,
        string $default,
        int $blockIndex,
        array $matrixContext
    ): array {
        $paths = [];
        $position = $matrixContext['position'];

        // Add index-specific paths
        if ($style && $style !== 'none') {
            $paths[] = "index/{$blockIndex}/style/{$style}/{$type}";
        }
        $paths[] = "index/{$blockIndex}/{$type}";

        // Add position-based paths
        if ($position['isFirst']) {
            if ($style && $style !== 'none') {
                $paths[] = "position/first/style/{$style}/{$type}";
            }
            $paths[] = "position/first/{$type}";
            $paths[] = "position/first/{$default}";
        }

        if ($position['isLast']) {
            if ($style && $style !== 'none') {
                $paths[] = "position/last/style/{$style}/{$type}";
            }
            $paths[] = "position/last/{$type}";
            $paths[] = "position/last/{$default}";
        }

        if (!$position['isFirst'] && !$position['isLast']) {
            if ($style && $style !== 'none') {
                $paths[] = "position/middle/style/{$style}/{$type}";
            }
            $paths[] = "position/middle/{$type}";
            $paths[] = "position/middle/{$default}";
        }

        // Add even/odd paths for alternating styles
        $isEven = ($blockIndex % 2) === 0;
        $evenOdd = $isEven ? 'even' : 'odd';
        
        if ($style && $style !== 'none') {
            $paths[] = "position/{$evenOdd}/style/{$style}/{$type}";
        }
        $paths[] = "position/{$evenOdd}/{$type}";

        return $paths;
    }
    /
**
     * Generates style-specific template paths with enhanced conditional logic.
     *
     * This method creates comprehensive style-specific template paths that support
     * conditional template selection based on block context, position, and relationships.
     *
     * @param string $type Block type handle
     * @param string|null $style Style parameter
     * @param string $default Default template name
     * @param array<string, mixed> $matrixContext Enhanced matrix context
     * @param Element|null $ctx Context element
     * @return array<string> Array of style-specific template paths
     */
    private static function generateStyleSpecificPaths(
        string $type,
        ?string $style,
        string $default,
        array $matrixContext,
        ?Element $ctx = null
    ): array {
        $paths = [];

        if (!$style || $style === 'none') {
            return $paths;
        }

        // Add conditional style paths based on context
        if ($ctx) {
            $contextType = $ctx?->section?->handle;
            $contextElementType = $ctx?->type?->handle;

            // Context-specific style paths
            if ($contextType && $contextElementType) {
                $paths[] = "style/{$style}/context/{$contextType}/{$contextElementType}/{$type}";
                $paths[] = "style/{$style}/context/{$contextType}/{$type}";
            }
        }

        // Add position-based style paths
        $position = $matrixContext['position'];
        
        if ($position['isFirst']) {
            $paths[] = "style/{$style}/position/first/{$type}";
        }
        
        if ($position['isLast']) {
            $paths[] = "style/{$style}/position/last/{$type}";
        }
        
        if (!$position['isFirst'] && !$position['isLast']) {
            $paths[] = "style/{$style}/position/middle/{$type}";
        }

        // Add relationship-based style paths
        if (isset($matrixContext['nextBlock'])) {
            $nextType = $matrixContext['nextBlock']['type'];
            $paths[] = "style/{$style}/before/{$nextType}/{$type}";
        }

        if (isset($matrixContext['prevBlock'])) {
            $prevType = $matrixContext['prevBlock']['type'];
            $paths[] = "style/{$style}/after/{$prevType}/{$type}";
        }

        // Add parent-aware style paths for nested matrices
        if (isset($matrixContext['parentBlock'])) {
            $parentType = $matrixContext['parentBlock']['type'];
            $parentField = $matrixContext['parentBlock']['fieldHandle'];
            
            if ($parentField) {
                $paths[] = "style/{$style}/nested/{$parentField}/{$parentType}/{$type}";
                $paths[] = "style/{$style}/nested/{$parentField}/{$type}";
            }
            
            if ($parentType) {
                $paths[] = "style/{$style}/nested/{$parentType}/{$type}";
            }
        }

        // Add field-specific style paths
        $fieldHandle = $matrixContext['fieldHandle'];
        if ($fieldHandle) {
            $paths[] = "style/{$style}/field/{$fieldHandle}/{$type}";
        }

        // Add standard style paths
        $paths[] = "style/{$style}/{$type}";
        $paths[] = "style/{$style}/{$default}";

        return $paths;
    }

    /**
     * Generates legacy template paths for renamed matrix block types.
     *
     * This method creates fallback template paths for matrix block types that
     * have been renamed, ensuring backward compatibility with existing templates.
     *
     * @param string $currentType Current block type handle
     * @param string|null $style Style parameter
     * @param string $default Default template name
     * @param array<string, mixed> $matrixContext Enhanced matrix context
     * @return array<string> Array of legacy template paths
     */
    private static function generateLegacyPaths(
        string $currentType,
        ?string $style,
        string $default,
        array $matrixContext
    ): array {
        $paths = [];

        // Common legacy naming patterns for matrix blocks
        $legacyPatterns = self::getLegacyTypePatterns($currentType);

        foreach ($legacyPatterns as $legacyType) {
            // Add style-specific legacy paths
            if ($style && $style !== 'none') {
                $paths[] = "style/{$style}/{$legacyType}";
                
                // Add position-aware legacy style paths
                $position = $matrixContext['position'];
                if ($position['isFirst']) {
                    $paths[] = "style/{$style}/position/first/{$legacyType}";
                }
                if ($position['isLast']) {
                    $paths[] = "style/{$style}/position/last/{$legacyType}";
                }
            }

            // Add standard legacy paths
            $paths[] = $legacyType;
            
            // Add position-aware legacy paths
            $position = $matrixContext['position'];
            if ($position['isFirst']) {
                $paths[] = "position/first/{$legacyType}";
            }
            if ($position['isLast']) {
                $paths[] = "position/last/{$legacyType}";
            }
        }

        return $paths;
    }

    /**
     * Generates conditional template paths based on block context and relationships.
     *
     * This method implements conditional template selection logic that allows
     * different templates to be used based on the block's context, relationships,
     * and dynamic conditions.
     *
     * @param MatrixBlock $block Current matrix block
     * @param string $type Block type handle
     * @param string|null $style Style parameter
     * @param string $default Default template name
     * @param array<string, mixed> $matrixContext Enhanced matrix context
     * @return array<string> Array of conditional template paths
     */
    private static function generateConditionalPaths(
        MatrixBlock $block,
        string $type,
        ?string $style,
        string $default,
        array $matrixContext
    ): array {
        $paths = [];

        // Add count-based conditional paths
        $blockCount = self::getBlockCountInField($block);
        if ($blockCount !== null) {
            $countConditions = self::getCountBasedConditions($blockCount);
            
            foreach ($countConditions as $condition) {
                if ($style && $style !== 'none') {
                    $paths[] = "conditional/{$condition}/style/{$style}/{$type}";
                }
                $paths[] = "conditional/{$condition}/{$type}";
            }
        }

        // Add type-sequence conditional paths
        $typeSequence = self::getTypeSequenceContext($block, $matrixContext);
        if (!empty($typeSequence)) {
            foreach ($typeSequence as $sequence) {
                if ($style && $style !== 'none') {
                    $paths[] = "conditional/sequence/{$sequence}/style/{$style}/{$type}";
                }
                $paths[] = "conditional/sequence/{$sequence}/{$type}";
            }
        }

        // Add field-state conditional paths
        $fieldState = self::getFieldStateConditions($block);
        foreach ($fieldState as $state) {
            if ($style && $style !== 'none') {
                $paths[] = "conditional/state/{$state}/style/{$style}/{$type}";
            }
            $paths[] = "conditional/state/{$state}/{$type}";
        }

        return $paths;
    }

    /**
     * Gets legacy type patterns for a given current type.
     *
     * This method returns common legacy naming patterns that might have been
     * used for a matrix block type before it was renamed.
     *
     * @param string $currentType Current block type handle
     * @return array<string> Array of potential legacy type names
     */
    private static function getLegacyTypePatterns(string $currentType): array
    {
        $patterns = [];

        // Common renaming patterns
        $commonReplacements = [
            // CamelCase to snake_case
            'Block' => '',
            'block' => '',
            'Type' => '',
            'type' => '',
            // Common abbreviations
            'Img' => 'Image',
            'Txt' => 'Text',
            'Btn' => 'Button',
            'Nav' => 'Navigation',
            'Sec' => 'Section',
            'Col' => 'Column',
            'Row' => 'Row',
        ];

        // Generate variations by removing common suffixes/prefixes
        foreach ($commonReplacements as $old => $new) {
            if (str_contains($currentType, $old)) {
                $patterns[] = str_replace($old, $new, $currentType);
            }
            if (str_contains($currentType, $new)) {
                $patterns[] = str_replace($new, $old, $currentType);
            }
        }

        // Add camelCase to snake_case conversion
        $snakeCase = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $currentType));
        if ($snakeCase !== $currentType) {
            $patterns[] = $snakeCase;
        }

        // Add snake_case to camelCase conversion
        $camelCase = lcfirst(str_replace('_', '', ucwords($currentType, '_')));
        if ($camelCase !== $currentType) {
            $patterns[] = $camelCase;
        }

        // Remove duplicates and current type
        $patterns = array_unique($patterns);
        $patterns = array_filter($patterns, fn($pattern) => $pattern !== $currentType && !empty($pattern));

        return array_values($patterns);
    }

    /**
     * Gets the total count of blocks in the same field.
     *
     * @param MatrixBlock $block The matrix block to count siblings for
     * @return int|null The total count of blocks in the field, or null if unavailable
     */
    private static function getBlockCountInField(MatrixBlock $block): ?int
    {
        try {
            $owner = $block->getOwner();
            $field = $block->getField();
            
            if (!$owner || !$field) {
                return null;
            }

            // Get all blocks in the same field
            $allBlocks = $owner->getFieldValue($field->handle);
            
            if ($allBlocks && method_exists($allBlocks, 'count')) {
                return $allBlocks->count();
            }
            
            if (is_array($allBlocks)) {
                return count($allBlocks);
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Gets count-based conditions for template selection.
     *
     * @param int $count Total number of blocks in field
     * @return array<string> Array of count-based condition names
     */
    private static function getCountBasedConditions(int $count): array
    {
        $conditions = [];

        // Specific count conditions
        $conditions[] = "count_{$count}";

        // Range conditions
        if ($count === 1) {
            $conditions[] = 'single';
        } elseif ($count <= 3) {
            $conditions[] = 'few';
        } elseif ($count <= 10) {
            $conditions[] = 'many';
        } else {
            $conditions[] = 'numerous';
        }

        // Even/odd conditions
        $conditions[] = ($count % 2 === 0) ? 'even_count' : 'odd_count';

        return $conditions;
    }

    /**
     * Gets type sequence context for conditional template selection.
     *
     * @param MatrixBlock $block Current matrix block
     * @param array<string, mixed> $matrixContext Enhanced matrix context
     * @return array<string> Array of sequence-based condition names
     */
    private static function getTypeSequenceContext(MatrixBlock $block, array $matrixContext): array
    {
        $sequences = [];
        $currentType = $matrixContext['blockType'];

        // Add previous-current-next sequence
        $prevType = $matrixContext['prevBlock']['type'] ?? null;
        $nextType = $matrixContext['nextBlock']['type'] ?? null;

        if ($prevType && $nextType) {
            $sequences[] = "{$prevType}_{$currentType}_{$nextType}";
        }

        if ($prevType) {
            $sequences[] = "{$prevType}_{$currentType}";
        }

        if ($nextType) {
            $sequences[] = "{$currentType}_{$nextType}";
        }

        // Add repetition patterns
        if ($prevType === $currentType) {
            $sequences[] = 'repeated_type';
            if ($nextType === $currentType) {
                $sequences[] = 'triple_repeat';
            }
        }

        if ($nextType === $currentType) {
            $sequences[] = 'type_continues';
        }

        return $sequences;
    }

    /**
     * Gets field state conditions for conditional template selection.
     *
     * @param MatrixBlock $block Current matrix block
     * @return array<string> Array of field state condition names
     */
    private static function getFieldStateConditions(MatrixBlock $block): array
    {
        $conditions = [];

        try {
            $owner = $block->getOwner();
            $field = $block->getField();
            
            if (!$owner || !$field) {
                return $conditions;
            }

            // Check if field has minimum blocks
            $allBlocks = $owner->getFieldValue($field->handle);
            $blockCount = 0;
            
            if ($allBlocks && method_exists($allBlocks, 'count')) {
                $blockCount = $allBlocks->count();
            } elseif (is_array($allBlocks)) {
                $blockCount = count($allBlocks);
            }

            // Add field configuration-based conditions
            if (isset($field->minBlocks) && $blockCount <= $field->minBlocks) {
                $conditions[] = 'at_minimum';
            }

            if (isset($field->maxBlocks) && $blockCount >= $field->maxBlocks) {
                $conditions[] = 'at_maximum';
            }

            // Add field type conditions
            $conditions[] = 'matrix_field';
            
            if ($field->handle) {
                $conditions[] = "field_{$field->handle}";
            }

        } catch (\Exception $e) {
            // Silently handle errors and return what we have
        }

        return $conditions;
    }