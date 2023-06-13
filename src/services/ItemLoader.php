<?php

namespace wabisoft\bonsaitwig\services;

use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\base\Element;

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
        $entry = ArrayHelper::getValue($variables, 'entry');
        if (!$entry instanceof Element) {
            throw new \InvalidArgumentException('ItemLoader::load() expects "entry" to be a valid Craft Element.');
        }

        $path = ArrayHelper::getValue($variables, 'path') ?: 'item';
        $style = ArrayHelper::getValue($variables, 'style') ?: null;
        $ctx = ArrayHelper::getValue($variables, 'ctx') ?: null;
        $default = ArrayHelper::getValue($variables, 'default') ?: 'default';
        $ctxPath = ArrayHelper::getValue($variables, 'ctxPath') ?: 'ctx';
        $ctxPath = StringHelper::trim($ctxPath, '/');

        $section = $entry->section->handle ?? $entry->group->handle;
        $type = $entry->type->handle ?? false;
        $slug = $entry->slug;

        $checkTemplates = [];

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
            'showItemHierarchy'
        );
    }
}
