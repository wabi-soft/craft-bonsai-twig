<?php

namespace wabisoft\bonsaitwig\enums;

/**
 * Enum for debug modes supported by the Bonsai Twig plugin.
 *
 * This enum defines the different debug modes that can be enabled
 * via the beastmode parameter in development mode. Each mode provides
 * different levels of debugging information and visualization.
 *
 * @author Wabisoft
 * @since 6.4.0
 */
enum DebugMode: string
{
    case DISABLED = '';
    case PATH = 'path';
    case HIERARCHY = 'hierarchy';
    case FULL = 'full';
    case ALL = 'all';

    /**
     * Checks if debug mode is enabled.
     *
     * Returns true for any debug mode except DISABLED, indicating
     * that some form of debug information should be displayed.
     *
     * @return bool True if debug mode is enabled, false otherwise
     */
    public function isEnabled(): bool
    {
        return $this !== self::DISABLED;
    }

    /**
     * Checks if this debug mode should show template paths.
     *
     * Returns true for debug modes that include template path information
     * in their output.
     *
     * @return bool True if paths should be shown, false otherwise
     */
    public function shouldShowPaths(): bool
    {
        return match($this) {
            self::PATH, self::HIERARCHY, self::FULL, self::ALL => true,
            default => false,
        };
    }

    /**
     * Checks if this debug mode should show hierarchy information.
     *
     * Returns true for debug modes that include template hierarchy
     * visualization in their output.
     *
     * @return bool True if hierarchy should be shown, false otherwise
     */
    public function shouldShowHierarchy(): bool
    {
        return match($this) {
            self::HIERARCHY, self::FULL, self::ALL => true,
            default => false,
        };
    }

    /**
     * Checks if this debug mode should show performance information.
     *
     * Returns true for debug modes that include performance metrics
     * and timing information in their output.
     *
     * @return bool True if performance info should be shown, false otherwise
     */
    public function shouldShowPerformance(): bool
    {
        return match($this) {
            self::FULL, self::ALL => true,
            default => false,
        };
    }

    /**
     * Creates a DebugMode from a string value.
     *
     * Provides a safe way to create DebugMode instances from string
     * values, with fallback to DISABLED for unknown values.
     *
     * @param string|null $value The string value to convert
     * @return self The corresponding DebugMode enum case
     */
    public static function fromString(?string $value): self
    {
        if ($value === null) {
            return self::DISABLED;
        }

        return match($value) {
            '' => self::ALL, // Empty string means show all (the ?beastmode case)
            'path' => self::PATH,
            'hierarchy' => self::HIERARCHY,
            'full' => self::FULL,
            'all' => self::ALL,
            default => self::DISABLED,
        };
    }

    /**
     * Checks if a debug mode value is valid for a specific template type.
     *
     * Validates that the debug mode is appropriate for the given template
     * type, allowing type-specific debug modes like 'entry', 'category', etc.
     * Now supports cross-template debugging for better development experience.
     *
     * @param string $debugValue The debug value to check
     * @param TemplateType $templateType The template type to validate against
     * @return bool True if the debug value is valid for the template type
     */
    public static function isValidForTemplateType(string $debugValue, TemplateType $templateType): bool
    {
        // Empty string (from ?beastmode) means show all
        if ($debugValue === '') {
            return true;
        }
        
        // Check if it's a general debug mode
        $generalModes = ['path', 'hierarchy', 'full', 'all'];
        if (in_array($debugValue, $generalModes)) {
            return true;
        }

        // Check if it matches the template type (entry, category, item, matrix)
        return $debugValue === $templateType->value;

        return false;
    }
}