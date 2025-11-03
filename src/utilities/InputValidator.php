<?php

namespace wabisoft\bonsaitwig\utilities;

use craft\base\Element;
use wabisoft\bonsaitwig\exceptions\InvalidElementException;
use wabisoft\bonsaitwig\utilities\SecurityUtils;

/**
 * Simplified input validation utility for basic parameter checking.
 *
 * This class provides essential validation methods for template loading functions,
 * focusing only on core functionality needed for a dev-only tool.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\utilities
 * @since 6.4.0
 */
class InputValidator
{
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
                throw new InvalidElementException(
                    expectedType: 'craft\base\Element',
                    actualValue: $element,
                    message: sprintf('Parameter "%s" is required and must be a valid Craft Element', $parameterName)
                );
            }
            return null;
        }

        if (!$element instanceof Element) {
            throw new InvalidElementException(
                expectedType: 'craft\base\Element',
                actualValue: $element,
                message: sprintf('Parameter "%s" must be a valid Craft Element, %s given', $parameterName, get_debug_type($element))
            );
        }

        return $element;
    }

    /**
     * Validates a MatrixBlock or Entry parameter for backward compatibility.
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
     * Basic string validation for essential parameters.
     *
     * @param mixed $value The value to validate
     * @param string $parameterName Name of the parameter for error messages
     * @param bool $required Whether the parameter is required
     * @return string The validated string
     * @throws \InvalidArgumentException If validation fails
     */
    public static function validateString(mixed $value, string $parameterName = 'parameter', bool $required = false): string
    {
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

        return trim((string) $value);
    }

    /**
     * Basic array validation.
     *
     * @param mixed $value The value to validate
     * @param string $parameterName Name of the parameter for error messages
     * @param bool $required Whether the parameter is required
     * @return array<mixed> The validated array
     * @throws \InvalidArgumentException If validation fails
     */
    public static function validateArray(mixed $value, string $parameterName = 'array', bool $required = false): array
    {
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

        return $value;
    }

    /**
     * Basic template path validation.
     *
     * @param array<string> $templates Array of template paths to validate
     * @return array<string> Validated template paths
     * @throws \InvalidArgumentException If validation fails
     */
    public static function validateTemplatePaths(array $templates): array
    {
        $validatedTemplates = self::validateArray($templates, 'templates', true);
        
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

    /**
     * Basic template variables validation.
     *
     * @param mixed $variables The variables array to validate
     * @return array<string, mixed> The validated variables array
     * @throws \InvalidArgumentException If validation fails
     */
    public static function validateTemplateVariables(mixed $variables): array
    {
        $validatedVars = self::validateArray($variables, 'variables', false);

        // Basic validation that all keys are strings
        foreach ($validatedVars as $key => $value) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException(
                    sprintf('Template variable keys must be strings, %s given for key', get_debug_type($key))
                );
            }
        }

        return $validatedVars;
    }

    /**
     * Basic debug mode validation.
     *
     * @param mixed $value The debug mode value to validate
     * @param mixed $templateType The template type (not used in simplified version)
     * @return \wabisoft\bonsaitwig\enums\DebugMode The debug mode enum
     */
    public static function validateDebugMode(mixed $value, mixed $templateType): \wabisoft\bonsaitwig\enums\DebugMode
    {
        // Convert to string and use DebugMode enum
        $stringValue = $value === null ? null : (string) $value;
        return \wabisoft\bonsaitwig\enums\DebugMode::fromString($stringValue);
    }
}
