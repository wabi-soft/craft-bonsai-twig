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
     * @param array  $allowedBeastmodeValues Array of allowed beastmode values
     * 
     * @return string The rendered template content
     * 
     * @throws SyntaxError If template syntax is invalid
     * @throws Exception If template cannot be found or other Craft errors
     * @throws RuntimeError If template runtime error occurs
     * @throws LoaderError If template cannot be loaded
     * @throws InvalidArgumentException If invalid parameters are provided
     */
    public static function load($templates, $variables, $basePath, $type = 'entry', $allowedBeastmodeValues = []): string
    {
        // Validate input parameters
        if (!is_array($templates)) {
            throw new InvalidArgumentException("Templates must be an array");
        }

        $isDev = Craft::$app->getConfig()->general->devMode;

        // Get the directory from variables or type
        $directory = $variables['path'] ?? $type;

        // Generate cache key based on template info
        $cacheKey = 'load:' . implode(',', $templates) . ':' . $type;
        
        // Try to get cached version first
        $result = self::getCached($cacheKey);
        if($result) {
            return $result;
        }

        // If no cache or dev mode, process templates
        if ($result === false) {
            foreach ($templates as $template) {
                // Use template path as is, since it's already properly formatted
                $fullPath = $basePath ? StringHelper::trim($basePath . '/' . $template, '/') : StringHelper::trim($template, '/');
                
                // Check if template exists before trying to render
                if(Craft::$app->view->doesTemplateExist($fullPath)) {
                    $content = Craft::$app->view->renderTemplate($fullPath, $variables);
                    
                    // In production, return content directly
                    if(!$isDev) {
                        return $content;
                    }

                    // Dev mode: Check beastmode parameter
                    $beastmodeValue = Craft::$app->request->getParam('beastmode');
                    $shouldShowDebug = $beastmodeValue !== null && (
                        $beastmodeValue === '' || // Empty value means show all
                        in_array($beastmodeValue, $allowedBeastmodeValues) // Check allowed values
                    );

                    // If debug is enabled, prepare debug info
                    if ($shouldShowDebug) {
                        // Process templates to remove directory prefix for display
                        $displayTemplates = array_map(function($path) use ($directory) {
                            // Don't modify paths that already have the directory prefix
                            return $path;
                        }, $templates);

                        $info = [
                            'directory' => $directory,
                            'templates' => $displayTemplates,
                            'currentTemplate' => $fullPath,
                            'type' => $type
                        ];
                        
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
        $errorMessage = sprintf(
            "Unable to find any matching templates. Tried:\n%s",
            implode("\n", array_map(fn($t) => "- $t", $templates))
        );
        
        Craft::error($errorMessage, __METHOD__);

        // In dev mode, throw exception with detailed info
        if($isDev) {
            throw new \RuntimeException($errorMessage);
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
     */
    private static function renderInfo($content, $info, $type = 'entry'): string
    {
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
