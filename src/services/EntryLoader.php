<?php

namespace wabisoft\bonsaitwig\services;

use craft\helpers\ArrayHelper;
use craft\base\Element;

/**
 * Entry Loader service
 */
class EntryLoader
{
    public static function load(array $variables = []): string
    {
        $entry = ArrayHelper::getValue($variables, 'entry');
        $path = ArrayHelper::getValue($variables, 'path') ?: 'entry';

        if (!$entry instanceof Element) {
            throw new \InvalidArgumentException('EntryLoader::load() expects "entry" to be a valid Craft Element.');
        }

        $section = $entry->section->handle ?? '';
        $type = $entry->type->handle ?? '';
        $slug = $entry->slug ?? '';
        $default = 'default';

        $checkTemplates = [
            $section . '/' . $type . '/' . $slug,
            $section . '/' . $slug,
            $section . '/' . $type,
            $section . '/' . $default,
            $section,
            $type,
            $default,
        ];

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            $path,
            'entry',
            'showEntryPath',
            'showEntryHierarchy'
        );
    }
}
