<?php

namespace wabisoft\bonsaitwig\services;

use craft\helpers\ArrayHelper;

/**
 * Entry Loader service
 */
class EntryLoader
{
    public static function load(array $variables = []): string
    {
        $entry = ArrayHelper::getValue($variables, 'entry');
        $path = ArrayHelper::getValue($variables, 'path') ?: 'entry';

        // clean up
        $section = $entry->section->handle;
        $type = $entry->type->handle;
        $slug = $entry->slug;
        $default = 'default';

        $checkTemplates = [];

        $checkTemplates[] = $section . '/' . $type . '/' . $slug;
        $checkTemplates[] = $section . '/' . $slug;
        $checkTemplates[] = $section . '/' . $type;
        $checkTemplates[] = $section . '/' . $default;
        $checkTemplates[] = $section;
        $checkTemplates[] = $type;
        $checkTemplates[] = $default;

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            $path,
            'entry',
            'showEntryPath',
            'showEntryHierarchy');
    }
}
