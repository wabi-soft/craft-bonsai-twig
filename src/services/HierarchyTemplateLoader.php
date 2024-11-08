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
    public static function load($templates, $variables, $basePath, $type = 'entry', $showPathParam = null, $showHierarchyParam = null, $showInfoParam = null): string
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
                    $shouldShowPath = Craft::$app->request->getParam($showPathParam);
                    $shouldShowHierarchy = Craft::$app->request->getParam($showHierarchyParam);
                    $shouldShowInfo = Craft::$app->request->getParam($showInfoParam);

                    if ($shouldShowPath || $shouldShowHierarchy || $shouldShowInfo) {
                        $info = ($shouldShowHierarchy || $shouldShowInfo)
                            ? [
                                'directory' => $basePath,
                                'templates' => array_map(function($path) {
                                    return str_replace('/', '/', $path);
                                }, $templates)
                            ]
                            : ['directory' => dirname($fullPath), 'templates' => [basename($fullPath)]];
                        
                        $content = self::renderInfo($content, Json::encode($info), $type);
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
        if (!self::isJson($info)) {
            $currentTemplate = $info;
            $info = Json::encode([
                'directory' => dirname($info),
                'templates' => [basename($info)],
                'currentTemplate' => $currentTemplate
            ]);
        } else {
            $decoded = json_decode($info, true);
            if (isset($decoded['templates']) && count($decoded['templates']) > 0) {
                $decoded['templates'] = array_values(array_unique($decoded['templates']));
                foreach ($decoded['templates'] as $template) {
                    $fullPath = $decoded['directory'] . '/' . $template;
                    if (Craft::$app->view->doesTemplateExist($fullPath)) {
                        $decoded['currentTemplate'] = $fullPath;
                        break;
                    }
                }
                $info = Json::encode($decoded);
            }
        }

        return Craft::$app->view->renderTemplate('_bonsai-twig/_partials/infobar', [
            'info' => $info, 
            'content' => $content,
            'type' => $type
        ]);
    }

    private static function isJson($string): bool
    {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
