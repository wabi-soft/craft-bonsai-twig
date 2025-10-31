<?php

namespace wabisoft\bonsaitwig\services;

use Craft;
use wabisoft\bonsaitwig\helpers\LoggerHelper;
use yii\base\Component;

/**
 * Field Inspector Service
 *
 * This service handles the extraction and inspection of field information for debugging purposes.
 * It provides detailed information about field types, nested structures (Matrix blocks),
 * relational field sources (Entries, Categories, Assets), and option-based fields (Dropdown, etc.).
 *
 * This service is primarily used in development mode with beastmode debugging enabled,
 * providing developers with comprehensive field information directly in the debug overlay.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\services
 * @since 7.1.0
 */
class FieldInspectorService extends Component
{
    /**
     * Extracts first-level field handles from an element.
     *
     * Returns an array of field handles that are directly available on the element,
     * along with their field types and nested information for complex fields.
     *
     * @param \craft\base\ElementInterface $element The element to extract field handles from
     * @return array|null Array of field information or null if no fields found
     */
    public function extractFieldHandles(\craft\base\ElementInterface $element): ?array
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
                    'type' => $this->getFieldTypeDisplayName($field),
                ];

                // Add nested information for relational and complex fields
                $nestedInfo = $this->extractNestedFieldInfo($field);
                if ($nestedInfo) {
                    $fieldData['nested'] = $nestedInfo;
                }

                $fieldInfo[] = $fieldData;
            }

            return $fieldInfo;
        } catch (\Exception $e) {
            // Silently fail if we can't get field layout
            Craft::info('Could not extract field handles: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Extracts nested field information for relational and complex field types.
     *
     * For Matrix fields, shows block types and their fields.
     * For relation fields (Entries, Categories, etc.), shows allowed sources.
     * For option-based fields (Dropdown, etc.), shows available options.
     *
     * @param \craft\base\FieldInterface $field The field to extract nested info from
     * @return array|null Nested field information or null if not applicable
     */
    public function extractNestedFieldInfo(\craft\base\FieldInterface $field): ?array
    {
        try {
            $className = get_class($field);

            // Debug logging in dev mode
            LoggerHelper::debug(
                'Extracting nested info for field: ' . ($field->handle ?? 'unknown') .
                ', Class: ' . $className
            );

            // Handle Matrix fields - show block types and their fields
            if ($className === 'craft\fields\Matrix') {
                return $this->extractMatrixBlockInfo($field);
            }

            // Handle Entries field - show allowed sections and types
            if ($className === 'craft\fields\Entries') {
                return $this->extractEntriesFieldInfo($field);
            }

            // Handle Categories field - show allowed groups
            if ($className === 'craft\fields\Categories') {
                return $this->extractCategoriesFieldInfo($field);
            }

            // Handle Assets field - show allowed volumes
            if ($className === 'craft\fields\Assets') {
                return $this->extractAssetsFieldInfo($field);
            }

            // Handle Users field
            if ($className === 'craft\fields\Users') {
                return ['type' => 'users', 'note' => 'User elements'];
            }

            // Handle Dropdown field
            if ($className === 'craft\fields\Dropdown') {
                return $this->extractOptionsFieldInfo($field, 'dropdown');
            }

            // Handle Radio Buttons field
            if ($className === 'craft\fields\RadioButtons') {
                return $this->extractOptionsFieldInfo($field, 'radio');
            }

            // Handle Checkboxes field
            if ($className === 'craft\fields\Checkboxes') {
                return $this->extractOptionsFieldInfo($field, 'checkboxes');
            }

            // Handle Multi-select field
            if ($className === 'craft\fields\MultiSelect') {
                return $this->extractOptionsFieldInfo($field, 'multiselect');
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
            LoggerHelper::warning('Could not extract nested field info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extracts block type information from a Matrix field.
     *
     * Supports both Craft 4 (Matrix Block Types) and Craft 5 (Entry Types).
     * Uses the same logic as MatrixLoader for consistency.
     *
     * @param \craft\fields\Matrix $field The Matrix field
     * @return array|null Block type information
     */
    private function extractMatrixBlockInfo($field): ?array
    {
        try {
            LoggerHelper::debug('Extracting matrix block info for field: ' . ($field->handle ?? 'unknown'));

            // Craft 5 uses Entry Types, Craft 4 uses Block Types
            $usesEntryTypes = method_exists($field, 'getEntryTypes');
            $blockTypes = $usesEntryTypes
                ? $field->getEntryTypes()
                : $field->getBlockTypes();

            LoggerHelper::debug('Using ' . ($usesEntryTypes ? 'Entry Types' : 'Block Types') . ', count: ' . count($blockTypes));

            if (empty($blockTypes)) {
                return null;
            }

            $blockInfo = [];
            foreach ($blockTypes as $blockType) {
                LoggerHelper::debug('Processing block type - Class: ' . get_class($blockType) . ', ID: ' . ($blockType->id ?? 'unknown'));

                $blockFields = [];

                // Get custom fields using the field layout
                // This works for both Craft 4 and 5
                $fieldLayout = $blockType->getFieldLayout();
                $customFields = $fieldLayout ? $fieldLayout->getCustomFields() : [];

                foreach ($customFields as $blockField) {
                    $blockFields[] = [
                        'handle' => $blockField->handle,
                        'name' => $blockField->name,
                        'type' => $this->getFieldTypeDisplayName($blockField),
                    ];
                }

                // Get the handle using the exact same approach
                // In both Craft 4 and 5, blockType/entryType should have a handle property
                // Use getHandle() method if available, fall back to direct property access
                $blockHandle = method_exists($blockType, 'getHandle')
                    ? $blockType->getHandle()
                    : ($blockType->handle ?? null);

                LoggerHelper::debug('Block handle after getHandle: ' . ($blockHandle ?? 'null'));

                // If still no handle, try converting to string (EntryType has __toString)
                if (!$blockHandle && method_exists($blockType, '__toString')) {
                    $blockHandle = (string) $blockType;
                    LoggerHelper::debug('Block handle after __toString: ' . $blockHandle);
                }

                // Final fallback
                $blockHandle = $blockHandle ?? 'unknown';
                $blockName = $blockType->name ?? 'Unnamed Block';

                LoggerHelper::debug('Final block handle: ' . $blockHandle . ', name: ' . $blockName);

                $blockInfo[] = [
                    'handle' => $blockHandle,
                    'name' => $blockName,
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
     * Extracts allowed section information from an Entries field.
     *
     * @param \craft\fields\Entries $field The Entries field
     * @return array|null Section information
     */
    private function extractEntriesFieldInfo($field): ?array
    {
        try {
            $sources = $field->sources;

            // Debug logging
            LoggerHelper::debug('Entries field sources: ' . print_r($sources, true));

            if ($sources === '*') {
                return [
                    'type' => 'entries',
                    'note' => 'All sections allowed',
                ];
            }

            if (empty($sources)) {
                return [
                    'type' => 'entries',
                    'note' => 'No sources configured',
                ];
            }

            $allowedSections = [];
            foreach ($sources as $source) {
                // Parse source format: "section:uid", "entryType:uid", or other formats
                if (is_string($source)) {
                    if (strpos($source, 'section:') === 0) {
                        // Craft 4 & 5: Section sources
                        $sectionUid = substr($source, 8);
                        LoggerHelper::debug('Looking up section with UID: ' . $sectionUid);

                        // Always use sections service for section lookups (Craft 4 & 5)
                        $section = \Craft::$app->sections->getSectionByUid($sectionUid);

                        if ($section) {
                            LoggerHelper::debug('Found section: ' . $section->handle . ' (' . $section->name . ')');
                            $allowedSections[] = [
                                'handle' => $section->handle,
                                'name' => $section->name,
                            ];
                        } else {
                            LoggerHelper::warning('Section not found for UID: ' . $sectionUid);
                        }
                    } elseif (strpos($source, 'entryType:') === 0) {
                        // Craft 5: Entry type specific sources
                        $entryTypeUid = substr($source, 10);
                        if (\Craft::$app->has('entries')) {
                            $entryType = \Craft::$app->entries->getEntryTypeByUid($entryTypeUid);
                            if ($entryType) {
                                $section = $entryType->getSection();
                                $allowedSections[] = [
                                    'handle' => $entryType->handle ?? $entryType->name,
                                    'name' => $entryType->name . ($section ? ' (' . $section->name . ')' : ''),
                                ];
                            }
                        }
                    }
                }
            }

            $result = [
                'type' => 'entries',
                'sources' => $allowedSections,
            ];

            LoggerHelper::debug('Entries field result: ' . print_r($result, true));

            return $result;
        } catch (\Exception $e) {
            LoggerHelper::error('Error extracting entries field info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extracts allowed group information from a Categories field.
     *
     * @param \craft\fields\Categories $field The Categories field
     * @return array|null Group information
     */
    private function extractCategoriesFieldInfo($field): ?array
    {
        try {
            $sources = $field->sources;

            if ($sources === '*') {
                return [
                    'type' => 'categories',
                    'note' => 'All groups allowed',
                ];
            }

            if (empty($sources)) {
                return [
                    'type' => 'categories',
                    'note' => 'No sources configured',
                ];
            }

            $allowedGroups = [];
            foreach ($sources as $source) {
                if (is_string($source) && strpos($source, 'group:') === 0) {
                    $groupUid = substr($source, 6);
                    $group = Craft::$app->categories->getGroupByUid($groupUid);
                    if ($group) {
                        $allowedGroups[] = [
                            'handle' => $group->handle,
                            'name' => $group->name,
                        ];
                    }
                }
            }

            return [
                'type' => 'categories',
                'sources' => $allowedGroups,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extracts allowed volume information from an Assets field.
     *
     * @param \craft\fields\Assets $field The Assets field
     * @return array|null Volume information
     */
    private function extractAssetsFieldInfo($field): ?array
    {
        try {
            $sources = $field->sources;

            if ($sources === '*') {
                return [
                    'type' => 'assets',
                    'note' => 'All volumes allowed',
                ];
            }

            if (empty($sources)) {
                return [
                    'type' => 'assets',
                    'note' => 'No sources configured',
                ];
            }

            $allowedVolumes = [];
            foreach ($sources as $source) {
                if (is_string($source) && strpos($source, 'volume:') === 0) {
                    $volumeUid = substr($source, 7);
                    $volume = Craft::$app->volumes->getVolumeByUid($volumeUid);
                    if ($volume) {
                        $allowedVolumes[] = [
                            'handle' => $volume->handle,
                            'name' => $volume->name,
                        ];
                    }
                }
            }

            return [
                'type' => 'assets',
                'sources' => $allowedVolumes,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extracts options from dropdown, radio, checkbox, and multiselect fields.
     *
     * @param \craft\base\FieldInterface $field The field with options
     * @param string $type The field type identifier
     * @return array|null Options information
     */
    private function extractOptionsFieldInfo($field, string $type): ?array
    {
        try {
            $options = $field->options ?? [];

            if (empty($options)) {
                return [
                    'type' => $type,
                    'note' => 'No options configured',
                ];
            }

            $optionsList = [];
            foreach ($options as $option) {
                $optionData = [
                    'label' => $option['label'] ?? '',
                    'value' => $option['value'] ?? '',
                ];

                // Check if this option is default
                if (isset($option['default']) && $option['default']) {
                    $optionData['isDefault'] = true;
                }

                $optionsList[] = $optionData;
            }

            return [
                'type' => $type,
                'options' => $optionsList,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Gets a human-readable display name for a field type.
     *
     * Converts field class names to readable type names for debugging display.
     *
     * @param \craft\base\FieldInterface $field The field to get the type name for
     * @return string Human-readable field type name
     */
    public function getFieldTypeDisplayName(\craft\base\FieldInterface $field): string
    {
        $className = get_class($field);

        // Extract the short class name (e.g., "PlainText" from "craft\fields\PlainText")
        $shortName = substr($className, strrpos($className, '\\') + 1);

        // Convert camelCase to Title Case with spaces
        $displayName = preg_replace('/([a-z])([A-Z])/', '$1 $2', $shortName);

        return $displayName ?? $className;
    }
}
