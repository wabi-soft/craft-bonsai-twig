<?php

namespace wabisoft\bonsaitwig\services;

use Craft;
use craft\helpers\Json;

use craft\helpers\StringHelper;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use wabisoft\bonsaitwig\BonsaiTwig;
use wabisoft\bonsaitwig\enums\TemplateType;

use wabisoft\bonsaitwig\exceptions\TemplateNotFoundException;
use wabisoft\bonsaitwig\utilities\InputValidator;


use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;

/**
 * HierarchyTemplateLoader Component
 *
 * Core service for hierarchical template loading focused on development workflow support.
 * Simplified architecture without performance monitoring, caching, or complex error reporting.
 * Provides basic template resolution with simple debug information display.
 *
 * This is a development-only tool that prioritizes simplicity and maintainability over
 * production optimizations.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\services
 * @since 6.4.0
 */
class HierarchyTemplateLoader extends Component
{
    /**
     * Track if the beastmode shortcut script has been added to the page
     */
    private static bool $shortcutScriptAdded = false;

    /**
     * Loads and renders a template from a hierarchical list of possible templates.
     *
     * Core method for template resolution focused on development workflow support.
     * Uses simple template existence checking without caching or performance optimization.
     * Provides basic debug information display when beastmode parameter is enabled.
     *
     * Development-focused features:
     * - Simple hierarchical template resolution with fallback patterns
     * - Basic debug information display without performance metrics
     * - Direct file system checks for template existence
     * - Enhanced btPath() context storage for complete HTML debug output
     * - Zero production overhead (debug features disabled in production)
     *
     * @param array<string> $templates Array of template paths to try loading in priority order
     * @param array<string, mixed> $variables Variables to pass to the template for rendering
     * @param string $basePath Base path to prepend to template paths (legacy parameter)
     * @param TemplateType|string $type Type of template being loaded (entry, category, item, matrix)
     * @param array<string> $allowedBeastmodeValues Array of allowed beastmode debug values (unused in simplified version)
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

            // In dev mode, always add the beastmode keyboard shortcut (once per page load)
            // Register at end of body to ensure it loads after other scripts
            if (!self::$shortcutScriptAdded) {
                $shortcutScript = Craft::$app->view->renderTemplate('_bonsai-twig/_partials/beastmode-shortcut');
                Craft::$app->view->registerScript($shortcutScript, \yii\web\View::POS_END);
                self::$shortcutScriptAdded = true;
            }

            // Dev mode: Check beastmode parameter and filter by type
            $beastmodeValue = Craft::$app->request->getParam('beastmode');
            $shouldShowDebug = false;

            if ($beastmodeValue !== null) {
                // Show debug if beastmode=all or empty string
                if ($beastmodeValue === 'all' || $beastmodeValue === '') {
                    $shouldShowDebug = true;
                } else {
                    // Parse comma-separated values and check if current type is included
                    $requestedTypes = array_map('trim', explode(',', $beastmodeValue));
                    if (in_array($templateType->value, $requestedTypes, true)) {
                        $shouldShowDebug = true;
                    }
                }
            }

            // If debug is enabled, prepare debug info
            if ($shouldShowDebug) {
                    
                // Process templates to remove directory prefix for display
                $displayTemplates = array_map(function(string $path) use ($directory): string {
                    // Don't modify paths that already have the directory prefix
                    return $path;
                }, $validatedTemplates);

                // Determine element kind for debug (entry vs category vs asset vs product) when available
                $elementKind = null;
                $debugElement = null;

                // Check for element in common variable names (entry, asset, category, etc.)
                $elementVarNames = ['entry', 'asset', 'category', 'user', 'product'];
                foreach ($elementVarNames as $varName) {
                    if (isset($validatedVariables[$varName]) && $validatedVariables[$varName] instanceof \craft\base\Element) {
                        $el = $validatedVariables[$varName];
                        $debugElement = $el;

                        // Determine element type
                        if ($el instanceof \craft\elements\Category) {
                            $elementKind = 'category';
                        } elseif ($el instanceof \craft\elements\Entry) {
                            $elementKind = 'entry';
                        } elseif ($el instanceof \craft\elements\Asset) {
                            $elementKind = 'asset';
                        } elseif (class_exists('craft\commerce\elements\Product') && $el instanceof \craft\commerce\elements\Product) {
                            $elementKind = 'product';
                        }

                        break;
                    }
                }

                // Extract field handles from the element for debugging
                $fieldHandles = null;
                $elementInfo = null;
                if ($debugElement) {
                    try {
                        $fieldHandles = self::extractFieldHandles($debugElement);
                    } catch (\Exception $e) {
                        // Silently fail field extraction to avoid breaking template loading
                        $fieldHandles = null;
                    }

                    // Extract element information for the header
                    $elementHandle = null;
                    $sectionHandle = null;
                    if ($debugElement instanceof \craft\elements\Entry) {
                        // Get section handle for entries
                        if ($debugElement->section) {
                            $sectionHandle = $debugElement->section->handle;
                        }
                        // Use entry type handle as fallback
                        if ($debugElement->type) {
                            $elementHandle = $debugElement->type->handle;
                        }
                    } elseif ($debugElement instanceof \craft\elements\Category) {
                        // Get group handle for categories
                        $group = $debugElement->getGroup();
                        if ($group) {
                            $elementHandle = $group->handle;
                        }
                    } elseif ($debugElement instanceof \craft\elements\Asset) {
                        // Get volume handle for assets
                        $volume = $debugElement->getVolume();
                        if ($volume) {
                            $elementHandle = $volume->handle;
                        }
                    } elseif (class_exists('craft\commerce\elements\Product') && $debugElement instanceof \craft\commerce\elements\Product) {
                        // Get product type handle for commerce products
                        $productType = $debugElement->getType();
                        if ($productType) {
                            $elementHandle = $productType->handle;
                        }
                    } else {
                        // Fallback for other element types
                        $elementHandle = $debugElement->slug ?? $debugElement->handle ?? null;
                    }

                    $elementInfo = [
                        'handle' => $elementHandle,
                        'section_handle' => $sectionHandle,
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
     * Renders simple debug information around template content in development mode.
     *
     * This method wraps the rendered template content with basic debug information
     * including the template resolution hierarchy and current template path.
     * Simplified version without performance metrics or complex styling.
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
        // Note: The beastmode keyboard shortcut is now loaded separately in the main load() method
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

    /**
     * Extracts field handles from an element for debugging purposes.
     *
     * Returns an array of field information including handle, name, type, and nested
     * information for complex fields like Matrix blocks and relational fields.
     *
     * @param \craft\base\ElementInterface $element The element to extract field handles from
     * @return array|null Array of field information or null if no fields found
     */
    private static function extractFieldHandles(\craft\base\ElementInterface $element): ?array
    {
        try {
            $fieldLayout = $element->getFieldLayout();

            if (!$fieldLayout) {
                return null;
            }

            $fields = $fieldLayout->getCustomFields();

            if (empty($fields)) {
                return null;
            }

            $fieldInfo = [];

            foreach ($fields as $field) {
                $fieldData = [
                    'handle' => $field->handle,
                    'name' => $field->name,
                    'type' => self::getFieldTypeDisplayName($field),
                ];

                // Add nested information for complex fields
                $nestedInfo = self::extractNestedFieldInfo($field);
                if ($nestedInfo) {
                    $fieldData['nested'] = $nestedInfo;
                }

                $fieldInfo[] = $fieldData;
            }

            return $fieldInfo;
        } catch (\Exception $e) {
            // Silently fail if we can't get field layout
            return null;
        }
    }

    /**
     * Gets a user-friendly display name for a field type.
     *
     * @param \craft\base\FieldInterface $field The field to get the display name for
     * @return string The display name for the field type
     */
    private static function getFieldTypeDisplayName(\craft\base\FieldInterface $field): string
    {
        $className = get_class($field);
        
        // Extract the class name without namespace
        $shortClassName = substr($className, strrpos($className, '\\') + 1);
        
        // Convert from CamelCase to readable format
        return preg_replace('/([a-z])([A-Z])/', '$1 $2', $shortClassName);
    }

    /**
     * Extracts nested field information for complex field types.
     *
     * @param \craft\base\FieldInterface $field The field to extract nested info from
     * @return array|null Nested field information or null if not applicable
     */
    private static function extractNestedFieldInfo(\craft\base\FieldInterface $field): ?array
    {
        try {
            $className = get_class($field);

            // Handle Matrix fields - show block types and their fields
            if ($className === 'craft\fields\Matrix') {
                return self::extractMatrixBlockInfo($field);
            }

            // Handle Entries field - show allowed sections
            if ($className === 'craft\fields\Entries') {
                return self::extractEntriesFieldInfo($field);
            }

            // Handle Categories field - show allowed groups
            if ($className === 'craft\fields\Categories') {
                return self::extractCategoriesFieldInfo($field);
            }

            // Handle Assets field - show allowed volumes
            if ($className === 'craft\fields\Assets') {
                return self::extractAssetsFieldInfo($field);
            }

            // Handle Users field
            if ($className === 'craft\fields\Users') {
                return ['type' => 'users', 'note' => 'User elements'];
            }

            // Handle option-based fields
            if (in_array($className, [
                'craft\fields\Dropdown',
                'craft\fields\RadioButtons', 
                'craft\fields\Checkboxes',
                'craft\fields\MultiSelect'
            ])) {
                return self::extractOptionsFieldInfo($field);
            }

            // Handle Lightswitch field
            if ($className === 'craft\fields\Lightswitch') {
                return [
                    'type' => 'lightswitch',
                    'note' => 'Boolean (true/false)',
                ];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extracts block type information from a Matrix field.
     *
     * @param \craft\fields\Matrix $field The Matrix field
     * @return array|null Block type information
     */
    private static function extractMatrixBlockInfo($field): ?array
    {
        try {
            // Craft 5 uses Entry Types, Craft 4 uses Block Types
            $usesEntryTypes = method_exists($field, 'getEntryTypes');
            $blockTypes = $usesEntryTypes
                ? $field->getEntryTypes()
                : $field->getBlockTypes();

            if (empty($blockTypes)) {
                return null;
            }

            $blockInfo = [];
            foreach ($blockTypes as $blockType) {
                $blockFields = [];

                // Get custom fields using the field layout
                $fieldLayout = $blockType->getFieldLayout();
                $customFields = $fieldLayout ? $fieldLayout->getCustomFields() : [];

                foreach ($customFields as $blockField) {
                    $blockFields[] = [
                        'handle' => $blockField->handle,
                        'name' => $blockField->name,
                        'type' => self::getFieldTypeDisplayName($blockField),
                    ];
                }

                $blockInfo[] = [
                    'handle' => $blockType->handle,
                    'name' => $blockType->name,
                    'fields' => $blockFields,
                ];
            }

            return [
                'type' => 'matrix',
                'blocks' => $blockInfo,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extracts source information from an Entries field.
     *
     * @param \craft\fields\Entries $field The Entries field
     * @return array|null Source information
     */
    private static function extractEntriesFieldInfo($field): ?array
    {
        try {
            $sources = [];
            $sourcesData = $field->sources ?? [];

            foreach ($sourcesData as $source) {
                if (is_string($source) && strpos($source, 'section:') === 0) {
                    $sectionUid = substr($source, 8);
                    $section = \Craft::$app->sections->getSectionByUid($sectionUid);
                    if ($section) {
                        $sources[] = [
                            'handle' => $section->handle,
                            'name' => $section->name,
                        ];
                    }
                }
            }

            return [
                'type' => 'entries',
                'sources' => $sources,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extracts source information from a Categories field.
     *
     * @param \craft\fields\Categories $field The Categories field
     * @return array|null Source information
     */
    private static function extractCategoriesFieldInfo($field): ?array
    {
        try {
            $sources = [];
            $sourcesData = $field->sources ?? [];

            foreach ($sourcesData as $source) {
                if (is_string($source) && strpos($source, 'group:') === 0) {
                    $groupUid = substr($source, 6);
                    $group = \Craft::$app->categories->getGroupByUid($groupUid);
                    if ($group) {
                        $sources[] = [
                            'handle' => $group->handle,
                            'name' => $group->name,
                        ];
                    }
                }
            }

            return [
                'type' => 'categories',
                'sources' => $sources,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extracts source information from an Assets field.
     *
     * @param \craft\fields\Assets $field The Assets field
     * @return array|null Source information
     */
    private static function extractAssetsFieldInfo($field): ?array
    {
        try {
            $sources = [];
            $sourcesData = $field->sources ?? [];

            foreach ($sourcesData as $source) {
                if (is_string($source) && strpos($source, 'volume:') === 0) {
                    $volumeUid = substr($source, 7);
                    $volume = \Craft::$app->volumes->getVolumeByUid($volumeUid);
                    if ($volume) {
                        $sources[] = [
                            'handle' => $volume->handle,
                            'name' => $volume->name,
                        ];
                    }
                }
            }

            return [
                'type' => 'assets',
                'sources' => $sources,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extracts option information from option-based fields.
     *
     * @param \craft\base\FieldInterface $field The field with options
     * @return array|null Option information
     */
    private static function extractOptionsFieldInfo($field): ?array
    {
        try {
            $className = get_class($field);
            $type = match ($className) {
                'craft\fields\Dropdown' => 'dropdown',
                'craft\fields\RadioButtons' => 'radio',
                'craft\fields\Checkboxes' => 'checkboxes',
                'craft\fields\MultiSelect' => 'multiselect',
                default => 'options',
            };

            $options = [];
            if (property_exists($field, 'options') && is_array($field->options)) {
                foreach ($field->options as $option) {
                    $options[] = [
                        'label' => $option['label'] ?? '',
                        'value' => $option['value'] ?? '',
                        'isDefault' => $option['default'] ?? false,
                    ];
                }
            }

            return [
                'type' => $type,
                'options' => $options,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
