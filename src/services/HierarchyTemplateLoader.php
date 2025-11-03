<?php

namespace wabisoft\bonsaitwig\services;

use Craft;
use craft\helpers\Json;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use wabisoft\bonsaitwig\BonsaiTwig;
use wabisoft\bonsaitwig\enums\DebugMode;
use wabisoft\bonsaitwig\enums\TemplateType;

use wabisoft\bonsaitwig\exceptions\TemplateNotFoundException;
use wabisoft\bonsaitwig\utilities\InputValidator;


use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;

/**
 * HierarchyTemplateLoader Component
 *
 * This component handles the core template loading and rendering logic based on hierarchical
 * template resolution. It supports development mode features like debug information display,
 * template path visualization, and performance monitoring.
 *
 * The loader implements template existence checking and comprehensive error handling
 * for production and development environments.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\services
 * @since 6.4.0
 */
class HierarchyTemplateLoader extends Component
{
    /**
     * Loads and renders a template from a hierarchical list of possible templates.
     *
     * This is the core method that handles template resolution and rendering.
     * It iterates through the provided template paths until it finds an existing template,
     * then renders it with the provided variables. In development mode, it can display
     * debug information about the template resolution process.
     *
     * Features:
     * - Hierarchical template resolution with fallback patterns
     * - Development mode debug information display
     * - Comprehensive error handling and logging
     * - Template existence validation before rendering
     * - Optimized path deduplication and early exit strategies
     * - Batch file system operations for improved performance
     *
     * @param array<string> $templates Array of template paths to try loading in priority order
     * @param array<string, mixed> $variables Variables to pass to the template for rendering
     * @param string $basePath Base path to prepend to template paths (legacy parameter)
     * @param TemplateType|string $type Type of template being loaded (entry, category, item, matrix)
     * @param array<string> $allowedBeastmodeValues Array of allowed beastmode debug values
     *
     * @return string The rendered template content or empty string if no template found
     *
     * @throws SyntaxError If template syntax is invalid
     * @throws Exception If template cannot be found or other Craft errors occur
     * @throws RuntimeError If template runtime error occurs during rendering
     * @throws LoaderError If template cannot be loaded by Twig
     * @throws InvalidArgumentException If invalid parameters are provided
     */
    public static function load(array $templates, array $variables, string $basePath, TemplateType|string $type = 'entry', array $allowedBeastmodeValues = []): string
    {
        // Convert string type to enum if needed
        $templateType = $type instanceof TemplateType ? $type : TemplateType::fromString((string) $type);

        // Validate and sanitize input parameters
        $validatedTemplates = InputValidator::validateTemplatePaths($templates);
        $validatedVariables = InputValidator::validateTemplateVariables($variables);
        $validatedBasePath = InputValidator::validateString($basePath, 'basePath', false, 255);

        // Initialize env flags and services before any early exit
        $isDev = Craft::$app->getConfig()->general->devMode;
        $plugin = BonsaiTwig::getInstance();

        if (empty($validatedTemplates)) {
            throw new TemplateNotFoundException([], 'template');
        }



        // Get the directory from variables or type
        $directory = (string) ($validatedVariables['path'] ?? $templateType->getDefaultPath());

        // Simple template resolution - check each template path in order
        $resolvedPath = null;
        $finalAttemptedPaths = [];
        
        foreach ($validatedTemplates as $template) {
            // Generate full path
            $fullPath = $validatedBasePath ? trim($validatedBasePath . '/' . $template, '/') : trim($template, '/');
            $finalAttemptedPaths[] = $fullPath;
            
            // Check if template exists
            if (Craft::$app->view->doesTemplateExist($fullPath)) {
                $resolvedPath = $fullPath;
                break;
            }
        }
            
        if ($resolvedPath !== null) {

            // ============================================================
            // DEV MODE ONLY: Store template resolution context for btPath()
            // ============================================================
            // These variables are only added in development mode to support
            // the btPath() Twig function which displays template hierarchies.
            // Zero overhead in production mode.
            if ($isDev) {
                $validatedVariables['_btTemplates'] = $validatedTemplates;

                // Find which original template corresponds to the resolved path
                // The resolvedPath comes from optimizedPaths (with basePath prepended),
                // but we need to find the matching original template from validatedTemplates
                $matchedOriginalTemplate = null;

                // Try to match by checking if the resolved path ends with each original template
                foreach ($validatedTemplates as $originalTemplate) {
                    // Build the full path for comparison (same logic as optimizeTemplatePaths)
                    $fullPath = $validatedBasePath
                        ? StringHelper::trim($validatedBasePath . '/' . $originalTemplate, '/')
                        : StringHelper::trim($originalTemplate, '/');

                    if ($fullPath === $resolvedPath) {
                        $matchedOriginalTemplate = $originalTemplate;
                        break;
                    }
                }

                $validatedVariables['_btResolvedTemplate'] = $matchedOriginalTemplate ?? $resolvedPath;
            }

            // Render the template
            $content = Craft::$app->view->renderTemplate($resolvedPath, $validatedVariables);
                
            // In production, return content directly
            if (!$isDev) {
                return $content;
            }

            // Dev mode: Simple beastmode parameter check
            $beastmodeValue = Craft::$app->request->getParam('beastmode');
            $shouldShowDebug = $beastmodeValue !== null;

            // If debug is enabled, prepare debug info
            if ($shouldShowDebug) {
                    
                // Process templates to remove directory prefix for display
                $displayTemplates = array_map(function(string $path) use ($directory): string {
                    // Don't modify paths that already have the directory prefix
                    return $path;
                }, $validatedTemplates);

                // Determine element kind for debug (entry vs category) when available
                $elementKind = null;
                $debugElement = null;
                if (isset($validatedVariables['entry']) && $validatedVariables['entry'] instanceof \craft\base\Element) {
                    $el = $validatedVariables['entry'];
                    $debugElement = $el;
                    $elementKind = ($el instanceof \craft\elements\Category) ? 'category' : (($el instanceof \craft\elements\Entry) ? 'entry' : null);
                }

                // Extract field handles from the element for debugging
                $fieldHandles = null;
                $elementInfo = null;
                if ($debugElement) {

                    // Extract element information for the header
                    // For matrix blocks (Entry elements in Craft 5), use the entry type handle
                    $elementHandle = null;
                    if ($debugElement instanceof \craft\elements\Entry && $debugElement->type) {
                        // Use entry type handle (works for both regular entries and matrix blocks)
                        $elementHandle = $debugElement->type->handle;
                    } else {
                        // Fallback for other element types
                        $elementHandle = $debugElement->slug ?? $debugElement->handle ?? null;
                    }

                    $elementInfo = [
                        'handle' => $elementHandle,
                        'title' => $debugElement->title ?? null,
                        'id' => $debugElement->id ?? null,
                        'type' => $elementKind,
                    ];
                }

                $info = [
                        'directory' => $directory,
                        'templates' => $displayTemplates,
                        'currentTemplate' => $resolvedPath,
                        'type' => $templateType->value,
                        'element_kind' => $elementKind,
                        'element_info' => $elementInfo,
                        'field_handles' => $fieldHandles,
                    ];
                    
                // Wrap content with enhanced debug info
                $displayType = $templateType->value . ($elementKind ? (' (' . $elementKind . ')') : '');
                $content = self::renderInfo($content, Json::encode($info), $displayType);
            }

            return $content;
        }

        // No template was found - handle error
        // In dev mode, throw exception with detailed info
        if ($isDev) {
            throw new TemplateNotFoundException($finalAttemptedPaths, $templateType->value);
        }

        // In production, return empty string
        return '';
    }

    /**
     * Renders debug information around template content in development mode.
     *
     * This method wraps the rendered template content with debug information
     * including the template resolution hierarchy, current template path, and
     * performance metrics. Only used when beastmode debugging is enabled.
     *
     * @param string $content Template content to wrap with debug information
     * @param string $info JSON encoded debug information containing paths and metadata
     * @param string $type Template type identifier (entry, category, item, matrix)
     *
     * @return string Content wrapped with debug information template
     */
    private static function renderInfo(string $content, string $info, string $type = 'entry'): string
    {
        // Render debug info template with content
        return Craft::$app->view->renderTemplate(
            template: '_bonsai-twig/_partials/infobar',
            variables: [
                'info' => $info,
                'content' => $content,
                'type' => $type,
            ]
        );
    }



    /**
     * Validates if a string contains valid JSON data.
     *
     * This utility method checks if the provided string is valid JSON by attempting
     * to decode it and checking for JSON parsing errors. Used for validating debug
     * information before processing.
     *
     * @param mixed $string String to validate for JSON format
     * @return bool True if the string contains valid JSON, false otherwise
     */
    private static function isJson(mixed $string): bool
    {
        return is_string($string) && json_decode($string) && json_last_error() === JSON_ERROR_NONE;
    }










}
