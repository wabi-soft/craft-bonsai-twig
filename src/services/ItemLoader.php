<?php

namespace wabisoft\bonsaitwig\services;

use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;

/**
 * Item Loader service
 */
class ItemLoader
{
    /**
     * @throws \Exception
     */
    public static function load(array $variables = []): string
    {

        $path =     ArrayHelper::getValue($variables, 'path') ?: 'item';
        $entry =    ArrayHelper::getValue($variables, 'entry');
        $style =    ArrayHelper::getValue($variables, 'style') ?: null;
        $ctx =      ArrayHelper::getValue($variables, 'ctx') ?: null;
        $default =  ArrayHelper::getValue($variables, 'default') ?: 'default';
        $ctxPath =  ArrayHelper::getValue($variables, 'ctxPath') ?: 'ctx';

        // Make sure we're passing an entry
        if(!$entry) {
            throw new \Exception('Item function missing required entry');
        }

        // Clean up the incoming vars
        $ctx = !is_string($ctx) ? $ctx : null;
        $style = $style != 'none' ? $style : null;
        $ctxPath = StringHelper::trim($ctxPath, '/');

        $section = $entry->section->handle ?? $entry->group->handle;
        $type = $entry->type->handle ?? false;
        $slug = $entry->slug;

        $checkTemplates = [];
        // Template hierarchy
        if($ctx) {
            if($style) {
                $checkTemplates[] = "{$section}/{$ctxPath}/{$ctx->section->handle}/{$ctx->type->handle}/{$style}";
            }
            if($type) {
                $checkTemplates[] = "{$section}/{$ctxPath}/{$ctx->section->handle}/{$ctx->type->handle}/{$type}";
                $checkTemplates[] = "{$section}/{$ctxPath}/{$ctx->section->handle}/{$ctx->type->handle}/{$default}";
            }
            if($style) {
                $checkTemplates[] = "{$section}/{$ctxPath}/{$ctx->section->handle}/{$style}";
            }
            if($type) {
                $checkTemplates[] = "{$section}/{$ctxPath}/{$ctx->section->handle}/{$type}";
            }
            $checkTemplates[] = "{$section}/{$ctxPath}/{$ctx->section->handle}/{$default}";
            $checkTemplates[] = "{$section}/{$ctxPath}/{$ctx->section->handle}";
        }

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
        $checkTemplates[] = "{$section}/{$default}";
        $checkTemplates[] = $section;
        $checkTemplates[] = $default;

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            $path,
            'item',
            'showItemPath',
            'showItemHierarchy');
    }


}
