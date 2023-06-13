<?php

namespace wabisoft\bonsaitwig\services;

use craft\helpers\ArrayHelper;
use craft\base\Element;

/**
 * Matrix Loader service
 */
class MatrixLoader
{
    /**
     * @throws \Exception
     */
    public static function load(array $variables = []): string
    {
        $block = ArrayHelper::getValue($variables, 'block');
        if (!$block instanceof Element) {
            throw new \InvalidArgumentException('MatrixLoader::load() expects "block" to be a valid Craft Element.');
        }

        $path = ArrayHelper::getValue($variables, 'path') ?: 'matrix';
        $style = ArrayHelper::getValue($variables, 'style');
        $ctx = ArrayHelper::getValue($variables, 'ctx');
        $ctxPath = ArrayHelper::getValue($variables, 'ctxPath') ?: 'ctx';

        $type = $block->type->handle;
        $default = 'default';

        $checkTemplates = [];
        $defaultTemplates = [$type, $default];

        if ($ctx) {
            $ctxPath = $ctxPath . '/' . $ctx->section->handle . '/' . $ctx->type->handle;

            $styleTemplates = $style ? [$ctxPath . '/style/' . $style . '/' . $type] : [];
            $typeTemplates = [$ctxPath . '/' . $type, $ctxPath . '/' . $default];

            $checkTemplates = array_merge($checkTemplates, $styleTemplates, $typeTemplates);
        }

        if($style && $style != 'none') {
            $checkTemplates[] = 'style/' . $style . '/' . $type;
        }

        $checkTemplates = array_merge($checkTemplates, $defaultTemplates);

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            $path,
            'item',
            'showMatrixPath',
            'showMatrixHierarchy');
    }
}
