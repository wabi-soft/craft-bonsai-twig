<?php

namespace wabisoft\bonsaitwig\utilities;

use Craft;
use craft\base\Element;
use wabisoft\bonsaitwig\enums\DebugMode;
use wabisoft\bonsaitwig\enums\TemplateType;
use wabisoft\bonsaitwig\exceptions\InvalidElementException;
use wabisoft\bonsaitwig\BonsaiTwig;

/**
 * Input validation utility class for service method parameters.
 *
 * This class provides comprehensive validation for all input parameters used
 * throughout the Bonsai Twig plugin, ensuring data integrity and security.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\utilities
 * @since 6.4.0
 */
class InputValidator
{
    /**
     * Maximum allowed length for string parameters.
     */
    private const MAX_STRING_LENGTH = 255;

    /**
     * Maximum allowed array size for template arrays.
     */
    private const MAX_ARRAY_SIZE = 100;

    /**
     * Allowed characters in handle/slug parameters.
     */
    private const HANDLE_PATTERN = '/^[a-zA-Z0-9_-]*$/';

    /**
     * Validates a Craft MatrixBlock parameter.
     *
     * @param mixed $element The element to validate
     * @param string $parameterName Name of the parameter for error messages
     * @param bool $required Whether the element is required
     * @return \craft\elements\MatrixBlock|null The validated MatrixBlock or null if not required and empty
     * @throws InvalidElementException If validation fails
     */
    public static function validateMatrixBlock(mixed $element, string $parameterName = 'block', bool $required = true): ?\craft\elements\MatrixBlock
    {
        if ($element === null || $element === '') {
            if ($required) {
                throw new InvalidElementException(
                    expectedType: 'craft\elements\MatrixBlock',
                    actualValue: $element,
                    message: sprintf('Parameter "%s" is required and must be a valid MatrixBlock', $parameterName)
                );
            }
            return null;
        }

        if (!$element instanceof \craft\elements\MatrixBlock) {
            // Provide helpful migration guidance for common Craft 4 to 5 scenarios
            $actualType = get_debug_type($element);
            $migrationHint = '';
            
            if ($element instanceof \craft\elements\Entry) {
                $migrationHint = "\n\nMigration Tip: If this field was converted from Matrix to Related Entries in Craft 5, use entryTemplates() instead:\n" .
                    "{{ entryTemplates({ entry: block }) }}";
            } elseif ($element instanceof \craft\base\Element) {
                $migrationHint = "\n\nMigration Tip: For non-Matrix elements, consider using:\n" .
                    "- entryTemplates() for Entry elements\n" .
                    "- categoryTemplates() for Category elements\n" .
                    "- itemTemplates() for other element types";
            }
            
            throw new InvalidElementException(
                expectedType: 'craft\elements\MatrixBlock',
                actualValue: $element,
                message: sprintf('Parameter "%s" must be a valid MatrixBlock, %s given%s', $parameterName, $actualType, $migrationHint)
            );
        }

        return $element;
    }

    /**
     * Validates a MatrixBlock or Entry parameter for backward compatibility.
     * 
     * This method supports Craft 4 to 5 migration scenarios where Matrix fields
     * have been converted to Related Entries fields but templates still use
     * the matrix template structure.
     *
     * @param mixed $element The element to validate
     * @param string $parameterName Name of the parameter for error messages
     * @param bool $required Whether the element is required
     * @return \craft\elements\MatrixBlock|\craft\elements\Entry|null The validated element
     * @throws InvalidElementException If validation fails
     */
    public static function validateMatrixBlockOrEntry(mixed $element, string $parameterName = 'block', bool $required = true): \craft\elements\MatrixBlock|\craft\elements\Entry|null
    {
        if ($element === null || $element === '') {
            if ($required) {
                throw new InvalidElementException(
                    expectedType: 'craft\elements\MatrixBlock or craft\elements\Entry',
                    actualValue: $element,
                    message: sprintf('Parameter "%s" is required and must be a valid MatrixBlock or Entry', $parameterName)
                );
            }
            return null;
        }

        if ($element instanceof \craft\elements\MatrixBlock || $element instanceof \craft\elements\Entry) {
            return $element;
        }

        throw new InvalidElementException(
            expectedType: 'craft\elements\MatrixBlock or craft\elements\Entry',
            actualValue: $element,
            message: sprintf('Parameter "%s" must be a valid MatrixBlock or Entry, %s given', $parameterName, get_debug_type($element))
        );
    }

    /**
     * Validates a Craft Element parameter.
     *
     * @param mixed $element The element to validate
     * @param string $parameterName Name of the parameter for error messages
     * @param bool $required Whether the element is required
     * @return Element|null The validated element or null if not required and empty
     * @throws InvalidElementException If validation fails
     */
    public static function validateElement(mixed $element, string $parameterName = 'element', bool $required = true): ?Element
    {
        if ($element === null || $element === '') {
            if ($required) {
                $exception = new InvalidElementException(
                    expectedType: 'craft\base\Element',
                    actualValue: $element,
                    message: sprintf('Parameter "%s" is required and must be a valid Craft Element', $parameterName)
                );
                
                // In development mode, provide enhanced error reporting
                if (Craft::$app->getConfig()->general->devMode) {
                    $plugin = BonsaiTwig::getInstance();
                    if ($plugin && isset($plugin->errorReportingService)) {
                        $errorReport = $plugin->errorReportingService->generateInvalidElementReport(
                            $exception,
                            $element,
                            ['parameter_name' => $parameterName, 'required' => $required]
                        );
                        
                        $detailedMessage = $plugin->errorReportingService->formatErrorForDisplay($errorReport);
                        throw new InvalidElementException(
                            expectedType: 'craft\base\Element',
                            actualValue: $element,
                            message: $detailedMessage
                        );
                    }
                }
                
                throw $exception;
            }
            return null;
        }

        if (!$element instanceof Element) {
            $exception = new InvalidElementException(
                expectedType: 'craft\base\Element',
                actualValue: $element,
                message: sprintf('Parameter "%s" must be a valid Craft Element, %s given', $parameterName, get_debug_type($element))
            );
            
            // In development mode, provide enhanced error reporting
            if (Craft::$app->getConfig()->general->devMode) {
                $plugin = BonsaiTwig::getInstance();
                if ($plugin && isset($plugin->errorReportingService)) {
                    $errorReport = $plugin->errorReportingService->generateInvalidElementReport(
                        $exception,
                        $element,
                        ['parameter_name' => $parameterName, 'required' => $required]
                    );
                    
                    $detailedMessage = $plugin->errorReportingService->formatErrorForDisplay($errorReport);
                    throw new InvalidElementException(
                        expectedType: 'craft\base\Element',
                        actualValue: $element,
                        message: $detailedMessage
                    );
                }
            }
            
            throw $exception;
        }

        return $element;
    }

    /**
     * Validates a string parameter with optional length and pattern constraints.
     *
     * @param mixed $value The value to validate
     * @param string $parameterName Name of the parameter for error messages
     * @param bool $required Whether the parameter is required
     * @param int $maxLength Maximum allowed length
     * @param string|null $pattern Optional regex pattern to match
     * @return string The validated and sanitized string
     * @throws \InvalidArgumentException If validation fails
     */
    public static function validateString(
        mixed $value,
        string $parameterName = 'parameter',
        bool $required = false,
        int $maxLength = self::MAX_STRING_LENGTH,
        ?string $pattern = null
    ): string {
        if ($value === null || $value === '') {
            if ($required) {
                throw new \InvalidArgumentException(
                    sprintf('Parameter "%s" is required and cannot be empty', $parameterName)
                );
            }
            return '';
        }

        if (!is_string($value) && !is_numeric($value)) {
            throw new \InvalidArgumentException(
                sprintf('Parameter "%s" must be a string, %s given', $parameterName, get_debug_type($value))
            );
        }

        $stringValue = (string) $value;

        // Check length
        if (strlen($stringValue) > $maxLength) {
            throw new \InvalidArgumentException(
                sprintf('Parameter "%s" exceeds maximum length of %d characters', $parameterName, $maxLength)
            );
        }

        // Check pattern if provided
        if ($pattern !== null && !preg_match($pattern, $stringValue)) {
            throw new \InvalidArgumentException(
                sprintf('Parameter "%s" contains invalid characters or format', $parameterName)
            );
        }

        // Basic sanitization - remove null bytes and control characters
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $stringValue);
        
        return trim($sanitized);
    }

    /**
     * Validates a handle parameter (alphanumeric, dash, underscore only).
     *
     * @param mixed $value The value to validate
     * @param string $parameterName Name of the parameter for error messages
     * @param bool $required Whether the parameter is required
     * @return string The validated handle
     * @throws \InvalidArgumentException If validation fails
     */
    public static function validateHandle(mixed $value, string $parameterName = 'handle', bool $required = false): string
    {
        $handle = self::validateString($value, $parameterName, $required, 64, self::HANDLE_PATTERN);
        
        // Additional handle-specific validation
        if (!empty($handle)) {
            // Handles cannot start with a number
            if (preg_match('/^[0-9]/', $handle)) {
                throw new \InvalidArgumentException(
                    sprintf('Parameter "%s" cannot start with a number', $parameterName)
                );
            }
        }

        return $handle;
    }

    /**
     * Validates an array parameter with size constraints.
     *
     * @param mixed $value The value to validate
     * @param string $parameterName Name of the parameter for error messages
     * @param bool $required Whether the parameter is required
     * @param int $maxSize Maximum allowed array size
     * @return array<mixed> The validated array
     * @throws \InvalidArgumentException If validation fails
     */
    public static function validateArray(
        mixed $value,
        string $parameterName = 'array',
        bool $required = false,
        int $maxSize = self::MAX_ARRAY_SIZE
    ): array {
        if ($value === null || $value === []) {
            if ($required) {
                throw new \InvalidArgumentException(
                    sprintf('Parameter "%s" is required and cannot be empty', $parameterName)
                );
            }
            return [];
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException(
                sprintf('Parameter "%s" must be an array, %s given', $parameterName, get_debug_type($value))
            );
        }

        if (count($value) > $maxSize) {
            throw new \InvalidArgumentException(
                sprintf('Parameter "%s" exceeds maximum size of %d elements', $parameterName, $maxSize)
            );
        }

        return $value;
    }

    /**
     * Validates an integer parameter with optional range constraints.
     *
     * @param mixed $value The value to validate
     * @param string $parameterName Name of the parameter for error messages
     * @param bool $required Whether the parameter is required
     * @param int|null $min Minimum allowed value
     * @param int|null $max Maximum allowed value
     * @return int|null The validated integer value or null if not required and empty
     * @throws \InvalidArgumentException If validation fails
     */
    public static function validateInteger(
        mixed $value,
        string $parameterName = 'integer',
        bool $required = false,
        ?int $min = null,
        ?int $max = null
    ): ?int {
        if ($value === null || $value === '') {
            if ($required) {
                throw new \InvalidArgumentException(
                    sprintf('Parameter "%s" is required', $parameterName)
                );
            }
            return null;
        }

        if (!is_numeric($value)) {
            throw new \InvalidArgumentException(
                sprintf('Parameter "%s" must be a numeric value, %s given', $parameterName, get_debug_type($value))
            );
        }

        $intValue = (int) $value;

        // Check if the conversion was lossy (i.e., it was a float)
        if ((string) $intValue !== (string) $value && (float) $value != $intValue) {
            throw new \InvalidArgumentException(
                sprintf('Parameter "%s" must be an integer, float given', $parameterName)
            );
        }

        // Check range constraints
        if ($min !== null && $intValue < $min) {
            throw new \InvalidArgumentException(
                sprintf('Parameter "%s" must be at least %d, %d given', $parameterName, $min, $intValue)
            );
        }

        if ($max !== null && $intValue > $max) {
            throw new \InvalidArgumentException(
                sprintf('Parameter "%s" must be at most %d, %d given', $parameterName, $max, $intValue)
            );
        }

        return $intValue;
    }

    /**
     * Validates a boolean parameter.
     *
     * @param mixed $value The value to validate
     * @param string $parameterName Name of the parameter for error messages
     * @param bool $required Whether the parameter is required
     * @return bool The validated boolean value
     * @throws \InvalidArgumentException If validation fails
     */
    public static function validateBoolean(mixed $value, string $parameterName = 'boolean', bool $required = false): bool
    {
        if ($value === null) {
            if ($required) {
                throw new \InvalidArgumentException(
                    sprintf('Parameter "%s" is required', $parameterName)
                );
            }
            return false;
        }

        // Accept various boolean representations
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));
            if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($lower, ['false', '0', 'no', 'off', ''], true)) {
                return false;
            }
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        throw new \InvalidArgumentException(
            sprintf('Parameter "%s" must be a boolean value, %s given', $parameterName, get_debug_type($value))
        );
    }

    /**
     * Validates debug mode parameter.
     *
     * @param mixed $value The debug mode value to validate
     * @param TemplateType $templateType The template type for context validation
     * @return DebugMode The validated debug mode
     * @throws \InvalidArgumentException If validation fails
     */
    public static function validateDebugMode(mixed $value, TemplateType $templateType): DebugMode
    {
        if ($value === null) {
            return DebugMode::DISABLED;
        }
        
        // Empty string means "show all" - this is the ?beastmode case
        if ($value === '') {
            return DebugMode::ALL;
        }

        $debugValue = self::validateString($value, 'debug mode', false, 20);
        
        // Handle template-specific debug values first
        if (in_array($debugValue, ['entry', 'category', 'item', 'matrix'])) {
            // Only show debug if the debug value matches the current template type
            if ($debugValue === $templateType->value) {
                return DebugMode::ALL; // Show debug for matching template type
            } else {
                return DebugMode::DISABLED; // Hide debug for non-matching template types
            }
        }

        // Handle general debug modes (path, hierarchy, full, all)
        if (!DebugMode::isValidForTemplateType($debugValue, $templateType)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid debug mode "%s". Allowed values: %s', 
                    $debugValue, 
                    implode(', ', array_merge(
                        array_map(fn($case) => $case->value, DebugMode::cases()),
                        ['entry', 'category', 'item', 'matrix']
                    ))
                )
            );
        }

        // Try to convert to DebugMode enum for general debug modes
        try {
            $debugMode = DebugMode::fromString($debugValue);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException(
                sprintf('Invalid debug mode "%s". Allowed values: %s', 
                    $debugValue, 
                    implode(', ', array_merge(
                        array_map(fn($case) => $case->value, DebugMode::cases()),
                        ['entry', 'category', 'item', 'matrix']
                    ))
                )
            );
        }

        return $debugMode;
    }

    /**
     * Validates template variables array.
     *
     * @param mixed $variables The variables array to validate
     * @return array<string, mixed> The validated variables array
     * @throws \InvalidArgumentException If validation fails
     */
    public static function validateTemplateVariables(mixed $variables): array
    {
        $validatedVars = self::validateArray($variables, 'variables', false, 50);

        // Validate that all keys are strings
        foreach ($validatedVars as $key => $value) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException(
                    sprintf('Template variable keys must be strings, %s given for key', get_debug_type($key))
                );
            }

            // Validate key format (no special characters that could cause issues)
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
                throw new \InvalidArgumentException(
                    sprintf('Template variable key "%s" contains invalid characters', $key)
                );
            }
        }

        return $validatedVars;
    }

    /**
     * Validates and sanitizes a complete service method parameter set.
     *
     * @param array<string, mixed> $variables The variables array to validate
     * @param TemplateType $templateType The template type for context validation
     * @return array<string, mixed> The validated and sanitized variables
     * @throws \InvalidArgumentException If validation fails
     */
    public static function validateServiceParameters(array $variables, TemplateType $templateType): array
    {
        $validated = [];

        // Validate the variables array structure
        $variables = self::validateTemplateVariables($variables);

        // Validate common parameters
        if (isset($variables['entry'])) {
            $validated['entry'] = self::validateElement($variables['entry'], 'entry', true);
        }

        if (isset($variables['block'])) {
            if ($templateType === TemplateType::MATRIX) {
                // Allow Entry elements for backward compatibility with Craft 4->5 migrations
                $validated['block'] = self::validateMatrixBlockOrEntry($variables['block'], 'block', true);
            } else {
                $validated['block'] = self::validateElement($variables['block'], 'block', true);
            }
        }

        if (isset($variables['ctx'])) {
            $validated['ctx'] = self::validateElement($variables['ctx'], 'ctx', false);
        }

        if (isset($variables['path'])) {
            $validated['path'] = SecurityUtils::sanitizeTemplatePath(
                self::validateString($variables['path'], 'path', false, 100)
            );
        }

        if (isset($variables['style'])) {
            $validated['style'] = self::validateHandle($variables['style'], 'style', false);
        }

        if (isset($variables['default'])) {
            $validated['default'] = self::validateHandle($variables['default'], 'default', false);
        }

        if (isset($variables['ctxPath'])) {
            $validated['ctxPath'] = SecurityUtils::sanitizeTemplatePath(
                self::validateString($variables['ctxPath'], 'ctxPath', false, 100)
            );
        }

        if (isset($variables['baseSite'])) {
            $validated['baseSite'] = self::validateHandle($variables['baseSite'], 'baseSite', false);
        }

        // Validate matrix-specific parameters for enhanced context awareness
        if ($templateType === TemplateType::MATRIX) {
            if (isset($variables['nextBlock'])) {
                $validated['nextBlock'] = self::validateElement($variables['nextBlock'], 'nextBlock', false);
            }

            if (isset($variables['prevBlock'])) {
                $validated['prevBlock'] = self::validateElement($variables['prevBlock'], 'prevBlock', false);
            }

            if (isset($variables['parentBlock'])) {
                $validated['parentBlock'] = self::validateElement($variables['parentBlock'], 'parentBlock', false);
            }

            if (isset($variables['blockIndex'])) {
                $validated['blockIndex'] = self::validateInteger($variables['blockIndex'], 'blockIndex', false, 0);
            }
        }

        // Copy over any other variables that passed initial validation
        foreach ($variables as $key => $value) {
            if (!isset($validated[$key])) {
                $validated[$key] = $value;
            }
        }

        return $validated;
    }

    /**
     * Validates template path array for security and format.
     *
     * @param array<string> $templates Array of template paths to validate
     * @return array<string> Validated and sanitized template paths
     * @throws \InvalidArgumentException If validation fails
     */
    public static function validateTemplatePaths(array $templates): array
    {
        $validatedTemplates = self::validateArray($templates, 'templates', true, 50);
        
        $sanitizedPaths = [];
        foreach ($validatedTemplates as $index => $template) {
            if (!is_string($template)) {
                throw new \InvalidArgumentException(
                    sprintf('Template path at index %d must be a string, %s given', $index, get_debug_type($template))
                );
            }
            
            $sanitizedPaths[] = SecurityUtils::sanitizeTemplatePath($template);
        }

        return $sanitizedPaths;
    }
}