<?php

namespace wabisoft\bonsaitwig\services;

use craft\helpers\ArrayHelper;
use craft\base\Element;

/**
 * Category Loader service
 */
class CategoryLoader
{
    public static function load(array $variables = []): string
    {
        $category = ArrayHelper::getValue($variables, 'entry');
        $path = ArrayHelper::getValue($variables, 'path') ?: 'category';

        if (!$category instanceof Element) {
            throw new \InvalidArgumentException('CategoryLoader::load() expects "entry" to be a valid Craft Element.');
        }

        $group = $category->group->handle ?? '';
        $slug = $category->slug ?? '';
        $default = 'default';

        $checkTemplates = [
            $group . '/' . $slug,
            $group . '/' . $default,
            $group,
            $default,
        ];

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            $path,
            'category',
            'showCategoryPath',
            'showCategoryHierarchy'
        );
    }
}
