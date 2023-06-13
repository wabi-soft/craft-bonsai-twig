<?php

namespace wabisoft\bonsaitwig\services;

use Craft;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Component;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use yii\base\Exception;
use yii\base\InvalidArgumentException;

class HierarchyTemplateLoader extends Component
{
    /**
     * @throws SyntaxError
     * @throws Exception
     * @throws RuntimeError
     * @throws LoaderError
     */
    public static function load($templates, $variables, $basePath, $type = 'entry', $showPathParam = null, $showHierarchyParam = null): string
    {
        if (!is_string($basePath) || !is_array($templates)) {
            throw new InvalidArgumentException("Invalid parameters provided");
        }

        $basePath = StringHelper::trim($basePath, '/');
        $isDev = Craft::$app->getConfig()->general->devMode;

        $cacheKey = 'load:' . $basePath . ':' . implode(',', $templates) . ':' . $type;
        $result = self::getCached($cacheKey);
        if($result) {
            return $result;
        }
        if ($result === false) {
            foreach ($templates as $template) {
                $fullPath = $basePath . '/' .$template;
                if(Craft::$app->view->doesTemplateExist($fullPath)) {
                    $content = Craft::$app->view->renderTemplate($fullPath, $variables);
                    if(!$isDev) {
                        return $content;
                    }
                    // if dev mode and param show stuff
                    if(Craft::$app->request->getParam($showPathParam)) {
                        $info = $fullPath;
                        $content = self::renderInfo($content, $info, $type);
                    }
                    if(Craft::$app->request->getParam($showHierarchyParam)) {
                        $info = Json::encode($templates);
                        $content = self::renderInfo($content, 'directory: ' . $basePath . ': ' . $info, $type);
                    }
                    Craft::$app->cache->set($cacheKey, $content, 3600);
                    return $content;
                }
            }
        }
        Craft::error(
            "Error locating any templates",
            __METHOD__
        );
        if($isDev) {
            throw new \RuntimeException('Unable to find templates in "' . $basePath . '" directory. Templates Dump: ' . Json::encode($templates));
        }
        return '';
    }


    private static function getCached($key) {
        $isDev = Craft::$app->getConfig()->general->devMode;
        if($isDev) {
            return false;
        }
        return Craft::$app->cache->get($key);
    }

    /**
     * @throws SyntaxError
     * @throws Exception
     * @throws RuntimeError
     * @throws LoaderError
     */
    private static function renderInfo($content, $info, $type = 'entry'): string
    {
        if($type == 'entry') {
            return Craft::$app->view->renderTemplate('_bonsai-twig/_partials/infobar', ['info' => $info, 'content' => $content]);
        }
        if($type == 'item') {
            return Craft::$app->view->renderTemplate('_bonsai-twig/_partials/infobar_group.twig', ['info' => $info, 'content' => $content]);
        }
        return $content;
    }
}
