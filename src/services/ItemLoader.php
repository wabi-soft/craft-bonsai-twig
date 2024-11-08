<?php

namespace wabisoft\bonsaitwig\services;

use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\base\Element;

/**
 * Service class for loading template paths based on Craft elements and context.
 * 
 * This class provides hierarchical template path resolution by examining an element's
 * section, type, style and context to determine the most appropriate template to load.
 * It follows a fallback pattern from most specific to most general template paths.
 */
class ItemLoader
{
    /**
     * Loads a template path based on the provided element and context parameters.
     * 
     * Generates a prioritized list of possible template paths based on:
     * - Context (ctx) section/type if provided
     * - Element's section and type
     * - Style variations
     * - Default fallbacks
     *
     * @param array $variables Configuration array containing:
     *        - entry: Required. Craft Element to base template paths on
     *        - path: Optional. Base path prefix (defaults to 'item')
     *        - style: Optional. Style variation name
     *        - ctx: Optional. Context Element for additional path variations
     *        - default: Optional. Default template name (defaults to 'default') 
     *        - ctxPath: Optional. Context path segment (defaults to 'ctx')
     * 
     * @throws \InvalidArgumentException If entry is not a valid Craft Element
     * @return string The resolved template path
     */
    public static function load(array $variables = []): string
    {
        // Extract and validate the required entry element
        $entry = ArrayHelper::getValue($variables, 'entry');
        if (!$entry instanceof Element) {
            throw new \InvalidArgumentException('ItemLoader::load() expects "entry" to be a valid Craft Element.');
        }

        // Extract optional configuration values with defaults
        $path = ArrayHelper::getValue($variables, 'path') ?: 'item';
        $style = ArrayHelper::getValue($variables, 'style') ?: null;
        $ctx = ArrayHelper::getValue($variables, 'ctx') ?: null;
        $default = ArrayHelper::getValue($variables, 'default') ?: 'default';
        $ctxPath = ArrayHelper::getValue($variables, 'ctxPath') ?: 'ctx';
        $ctxPath = StringHelper::trim($ctxPath, '/');

        // Get entry properties for path building
        $section = $entry->section->handle ?? $entry->group->handle;
        $type = $entry->type->handle ?? false;
        $slug = $entry->slug;

        // Build array of possible template paths in order of specificity
        $checkTemplates = [];

        // Add context-specific template paths if context is provided
        if($ctx) {
            // Most specific: section/ctx/contextSection/contextType/style
            if($style) {
                $checkTemplates[] = "{$section}/{$ctxPath}/{$ctx->section->handle}/{$ctx->type->handle}/{$style}";
            }
            // Type-specific context paths
            if($type) {
                $checkTemplates[] = "{$section}/{$ctxPath}/{$ctx->section->handle}/{$ctx->type->handle}/{$type}";
                $checkTemplates[] = "{$section}/{$ctxPath}/{$ctx->section->handle}/{$ctx->type->handle}/{$default}";
            }
            // Style variations at context section level
            if($style) {
                $checkTemplates[] = "{$section}/{$ctxPath}/{$ctx->section->handle}/{$style}";
            }
            // Type variations at context section level
            if($type) {
                $checkTemplates[] = "{$section}/{$ctxPath}/{$ctx->section->handle}/{$type}";
            }
            // Default context section fallbacks
            $checkTemplates[] = "{$section}/{$ctxPath}/{$ctx->section->handle}/{$default}";
            $checkTemplates[] = "{$section}/{$ctxPath}/{$ctx->section->handle}";
        }

        // Add non-context template paths
        if($style) {
            if($type) {
                $checkTemplates[] = "{$section}/{$type}/{$style}";
            }
            $checkTemplates[] = "{$section}/{$style}";
        }
        if($type) {
            $checkTemplates[] = "{$section}/{$type}/{$slug}";
            $checkTemplates[] = "{$section}/{$type}";
            $checkTemplates[] = "{$section}/{$type}/{$default}";
        }
        
        // Add most general fallback paths
        $checkTemplates[] = "{$section}/{$default}";
        $checkTemplates[] = $section;
        $checkTemplates[] = $default;

        // Use HierarchyTemplateLoader to find first matching template
        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            $path,
            'item',
            'showItemPath',
            'showItemHierarchy', 
            'showItemInfo'
        );
    }
}
