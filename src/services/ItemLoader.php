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
        $style = ArrayHelper::getValue($variables, 'style');
        $ctx = ArrayHelper::getValue($variables, 'ctx');
        $default = ArrayHelper::getValue($variables, 'default') ?: 'default';
        $ctxPath = ArrayHelper::getValue($variables, 'ctxPath') ?: 'ctx';
        $ctxPath = StringHelper::trim($ctxPath, '/');

        $section = $entry->section->handle ?? $entry->group->handle;
        $type = $entry->type->handle;
        $slug = $entry->slug;

        $checkTemplates = [];
        $defaultTemplates = [$default, $section, $default];

        if($ctx && $ctx->section) {
            $ctxSectionType = "{$section}/{$ctxPath}/{$ctx->section->handle}/{$ctx->type->handle}";
            $ctxSection = "{$section}/{$ctxPath}/{$ctx->section->handle}";

            $ctxTemplates = $style ? [$ctxSectionType.'/'.$style, $ctxSection.'/'.$style] : [];
            $typeTemplates = $type ? [$ctxSectionType.'/'.$type, $ctxSectionType.'/'.$default, $ctxSection.'/'.$type] : [];

            $checkTemplates = array_merge($checkTemplates, $ctxTemplates, $typeTemplates, [$ctxSection.'/'.$default, $ctxSection]);
        }

        if($style) {
            $styleTemplates = $type ? ["{$section}/{$type}/{$style}", "{$section}/{$style}"] : [];
            $checkTemplates = array_merge($checkTemplates, $styleTemplates);
        }

        if($type) {
            $typeTemplates = ["{$section}/{$type}/{$slug}", "{$section}/{$type}", "{$section}/{$type}/{$default}"];
            $checkTemplates = array_merge($checkTemplates, $typeTemplates);
        }

        $checkTemplates = array_merge($checkTemplates, $defaultTemplates);

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
