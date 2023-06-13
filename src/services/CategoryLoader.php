<?php

namespace wabisoft\bonsaitwig\services;

use Craft;
use yii\base\Component;
use craft\helpers\ArrayHelper;

/**
 * Category Loader service
 */
class CategoryLoader extends Component
{
    public static function load(array $variables = []): string
    {
        $entry = ArrayHelper::getValue($variables, 'entry');
        $path = ArrayHelper::getValue($variables, 'path') ?: 'category';

        // clean up
        $group = $entry->group->handle;
        $slug = $entry->slug;
        $default = 'default';

        $checkTemplates = [];

        $checkTemplates[] = $group . '/' . $slug;
        $checkTemplates[] = $group . '/' . $default;
        $checkTemplates[] = $group;
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
