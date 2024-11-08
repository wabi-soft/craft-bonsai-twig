<?php

namespace wabisoft\bonsaitwig\services;

use craft\helpers\ArrayHelper;
use craft\base\Element;

/**
 * Service class for loading template paths based on Craft matrix blocks.
 * 
 * This class provides hierarchical template path resolution by examining a matrix block's
 * type, style, and context to determine the most appropriate template to load.
 * It follows a fallback pattern from most specific to most general template paths.
 */
class MatrixLoader
{
    /**
     * Loads a template path based on the provided matrix block and configuration.
     * 
     * Generates a prioritized list of possible template paths based on:
     * - Context-specific paths (if context provided)
     * - Style variations (if style provided)
     * - Block type
     * - Default fallback
     *
     * @param array $variables Configuration array containing:
     *        - block: Required. Craft Matrix Block element to base template paths on
     *        - path: Optional. Base path prefix (defaults to 'matrix')
     *        - style: Optional. Style variation name
     *        - ctx: Optional. Context Element for additional path variations
     *        - ctxPath: Optional. Context path segment (defaults to 'ctx')
     * 
     * @throws \InvalidArgumentException If block is not a valid Craft Element
     * @return string The resolved template path
     */
    public static function load(array $variables = []): string
    {
        // Extract and validate the required block element
        $block = ArrayHelper::getValue($variables, 'block');
        if (!$block instanceof Element) {
            throw new \InvalidArgumentException('MatrixLoader::load() expects "block" to be a valid Craft Element.');
        }

        // Extract optional configuration values with defaults
        $path = ArrayHelper::getValue($variables, 'path') ?: 'matrix';
        $style = ArrayHelper::getValue($variables, 'style');
        $ctx = ArrayHelper::getValue($variables, 'ctx');
        $ctxPath = ArrayHelper::getValue($variables, 'ctxPath') ?: 'ctx';

        // Get block properties for path building
        $type = $block?->type?->handle;
        $default = 'default';

        // Build array of possible template paths
        $checkTemplates = [];
        $defaultTemplates = [$type, $default];  // Fallback templates

        // Add context-specific paths if context is provided
        if ($ctx) {
            $ctxPath = "{$ctxPath}/{$ctx?->section?->handle}/{$ctx?->type?->handle}";

            $styleTemplates = $style ? ["{$ctxPath}/style/{$style}/{$type}"] : [];
            $typeTemplates = ["{$ctxPath}/{$type}", "{$ctxPath}/{$default}"];

            $checkTemplates = array_merge($checkTemplates, $styleTemplates, $typeTemplates);
        }

        if ($style && $style != 'none') {
            $checkTemplates[] = "style/{$style}/{$type}";
        }

        // Add default templates as final fallback
        $checkTemplates = array_merge($checkTemplates, $defaultTemplates);

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            $path,
            'item',
            'showMatrixPath',
            'showMatrixHierarchy',
            'showMatrixInfo'
        );
    }
}
