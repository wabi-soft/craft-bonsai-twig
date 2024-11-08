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

/**
 * HierarchyTemplateLoader Component
 * 
 * This component handles the loading and rendering of templates based on a hierarchical structure.
 * It supports development mode features like showing template paths and hierarchy information.
 *
 * @package wabisoft\bonsaitwig\services
 * @since 1.0.0
 */
class HierarchyTemplateLoader extends Component
{
    /**
     * Loads and renders a template from a hierarchical list of possible templates.
     *
     * @param array  $templates         Array of template paths to try loading
     * @param array  $variables         Variables to pass to the template
     * @param string $basePath         Base path to prepend to template paths
     * @param string $type             Type of template being loaded (default: 'entry')
     * @param string|null $showPathParam      URL parameter to trigger path display
     * @param string|null $showHierarchyParam URL parameter to trigger hierarchy display
     * @param string|null $showInfoParam      URL parameter to trigger info display
     * 
     * @return string The rendered template content
     * 
     * @throws SyntaxError If template syntax is invalid
     * @throws Exception If template cannot be found or other Craft errors
     * @throws RuntimeError If template runtime error occurs
     * @throws LoaderError If template cannot be loaded
     * @throws InvalidArgumentException If invalid parameters are provided
     */
    public static function load($templates, $variables, $basePath, $type = 'entry', $showPathParam = null, $showHierarchyParam = null, $showInfoParam = null): string
    {
        // Validate input parameters
        if (!is_string($basePath) || !is_array($templates)) {
            throw new InvalidArgumentException("Invalid parameters provided");
        }

        // Normalize base path by trimming slashes
        $basePath = StringHelper::trim($basePath, '/');
        $isDev = Craft::$app->getConfig()->general->devMode;

        // Generate cache key based on template info
        $cacheKey = 'load:' . $basePath . ':' . implode(',', $templates) . ':' . $type;
        
        // Try to get cached version first
        $result = self::getCached($cacheKey);
        if($result) {
            return $result;
        }

        // If no cache or dev mode, process templates
        if ($result === false) {
            foreach ($templates as $template) {
                $fullPath = $basePath . '/' .$template;
                
                // Check if template exists before trying to render
                if(Craft::$app->view->doesTemplateExist($fullPath)) {
                    $content = Craft::$app->view->renderTemplate($fullPath, $variables);
                    
                    // In production, return content directly
                    if(!$isDev) {
                        return $content;
                    }

                    // Dev mode: Check for debug parameters
                    $shouldShowPath = Craft::$app->request->getParam($showPathParam);
                    $shouldShowHierarchy = Craft::$app->request->getParam($showHierarchyParam);
                    $shouldShowInfo = Craft::$app->request->getParam($showInfoParam);

                    // If any debug parameters are set, prepare debug info
                    if ($shouldShowPath || $shouldShowHierarchy || $shouldShowInfo) {
                        $info = ($shouldShowHierarchy || $shouldShowInfo)
                            ? [
                                'directory' => $basePath,
                                'templates' => array_map(function($path) {
                                    return str_replace('/', '/', $path);
                                }, $templates)
                            ]
                            : ['directory' => dirname($fullPath), 'templates' => [basename($fullPath)]];
                        
                        // Wrap content with debug info
                        $content = self::renderInfo($content, Json::encode($info), $type);
                    }

                    // Cache the result for an hour
                    Craft::$app->cache->set($cacheKey, $content, 3600);
                    return $content;
                }
            }
        }

        // Log error if no template was found
        Craft::error(
            "Error locating any templates",
            __METHOD__
        );

        // In dev mode, throw exception with detailed info
        if($isDev) {
            throw new \RuntimeException('Unable to find templates in "' . $basePath . '" directory. Templates Dump: ' . Json::encode($templates));
        }

        // In production, return empty string
        return '';
    }

    /**
     * Attempts to retrieve cached template content.
     *
     * @param string $key Cache key to lookup
     * @return string|false Cached content or false if not found/dev mode
     */
    private static function getCached(string $key): string|false
    {
        if (Craft::$app->getConfig()->general->devMode) {
            return false;
        }
        return Craft::$app->cache->get($key);
    }

    /**
     * Renders debug information around template content.
     *
     * @param string $content Template content to wrap
     * @param string $info JSON encoded debug information
     * @param string $type Template type identifier
     * 
     * @return string Content wrapped with debug information
     * 
     * @throws SyntaxError
     * @throws Exception
     * @throws RuntimeError
     * @throws LoaderError
     */
    private static function renderInfo($content, $info, $type = 'entry'): string
    {
        // Check if info is already JSON encoded
        if (!self::isJson($info)) {
            $currentTemplate = $info;
            $info = Json::encode([
                'directory' => dirname($info),
                'templates' => [basename($info)],
                'currentTemplate' => $currentTemplate
            ]);
        } else {
            // Process JSON info to find current template
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

        // Render debug info template with content
        return Craft::$app->view->renderTemplate(
            template: '_bonsai-twig/_partials/infobar',
            variables: [
                'info' => $info, 
                'content' => $content,
                'type' => $type
            ]
        );
    }

    /**
     * Validates if a string is valid JSON.
     *
     * @param mixed $string String to validate
     * @return bool True if valid JSON
     */
    private static function isJson(mixed $string): bool
    {
        return is_string($string) && json_decode($string) && json_last_error() === JSON_ERROR_NONE;
    }
}
