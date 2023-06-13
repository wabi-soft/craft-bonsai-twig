<?php

namespace wabisoft\bonsaitwig\services;

use Craft;
use yii\base\Component;
use craft\helpers\Json;
use craft\helpers\StringHelper;

/**
 * Hierarchy Template Loader service
 */
class HierarchyTemplateLoader extends Component
{
    public static function load($templates, $variables, $basePath, $type = 'entry', $showPathParam = null, $showHierarchyParam = null) {
        $basePath = StringHelper::trim($basePath, '/');
        $isDev = Craft::$app->getConfig()->general->devMode;

        foreach ($templates as $template) {
            if(Craft::$app->view->doesTemplateExist($basePath . '/' .$template)) {
                $content = Craft::$app->view->renderTemplate($basePath . '/' . $template, $variables);
                if(!$isDev) {
                    return $content;
                }
                // if dev mode and param show stuff
                if(Craft::$app->request->getParam($showPathParam)) {
                    $info = $basePath . '/' . $template;
                    $content = self::renderInfo($content, $info, $type);
                }
                if(Craft::$app->request->getParam($showHierarchyParam)) {
                    $info = Json::encode($templates);
                    $content = self::renderInfo($content, 'directory: ' . $basePath . ': ' . $info, $type);
                }
                return $content;
            }
        }
        Craft::error(
            "Error locating any templates",
            __METHOD__
        );
        if($isDev) {
            return '<div><strong>Unable to find templates in "<span style="color: red;">' . $basePath  . '</span>" directory. Templates Dump:</strong></div>' . '<div style="font-size: 12px; margin-bottom: 20px; font-family: monospace;">' . Json::encode($templates) . '</div>';
        }
        return '';
    }

    private static function renderInfo($content, $info, $type = 'entry') {
        if($type == 'entry') {
            $infoBar = '<div class="" style="color: red; font-size: 11px; font-family: monospace; position: fixed; top: 0; left: 0; z-index: 10; background-color: rgba(255,255,255,.8)">' . $info . '</div>';
            return $infoBar . $content;
        }
        if($type == 'item') {
            $infoBar = '<span class="opacity-0 hover:opacity-100 group-hover:opacity-100" style="color: red; font-size: 11px; font-family: monospace; position: absolute; z-index: 10; background-color: rgba(255,255,255,.8)">' . $info . '</span>';
            return '<div class="group" style="position: relative;">' . $infoBar . $content . '</div>';
        }
        return $content;
    }
}
