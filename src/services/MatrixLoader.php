<?php

namespace wabisoft\bonsaitwig\services;

use craft\helpers\ArrayHelper;

/**
 * Matrix Loader service
 */
class MatrixLoader
{
    public static function load(array $variables = []): string
    {
        $block =    ArrayHelper::getValue($variables, 'block');
        $path =     ArrayHelper::getValue($variables, 'path') ?: 'matrix';
        $style =    ArrayHelper::getValue($variables, 'style') ?: null;
        $ctx =      ArrayHelper::getValue($variables, 'ctx') ?: null;
        $ctxPath =  ArrayHelper::getValue($variables, 'ctxPath') ?: 'ctx';

        // clean up
        $type = $block->type->handle;
        $default = 'default';

        $checkTemplates = [];

        // Template hierarchy

        if($ctx) {
            if($style) {
                $checkTemplates[] = $ctxPath . '/' .  $ctx->section->handle . '/' . $ctx->type->handle . '/style/' . $style . '/' . $type;
            }
            $checkTemplates[] = $ctxPath . '/' . $ctx->section->handle . '/' . $ctx->type->handle . '/' . $type;
            $checkTemplates[] = $ctxPath . '/' . $ctx->section->handle . '/' . $ctx->type->handle . '/' . $default;
            $checkTemplates[] = $ctxPath . '/' . $ctx->section->handle . '/' . $type;
        }

        if($style && !$style != 'none') {
            $checkTemplates[] = 'style/' . $style . '/' . $type;
        }
        $checkTemplates[] = $type;
        $checkTemplates[] = $default;

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            $path,
            'item',
            'showMatrixPath',
            'showMatrixHierarchy');
    }
}
