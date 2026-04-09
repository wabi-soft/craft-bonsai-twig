<?php

namespace wabisoft\bonsaitwig\enums;

/**
 * Enum for template types supported by the Bonsai Twig plugin.
 *
 * This enum defines the different types of templates that can be loaded
 * by the plugin's hierarchical template resolution system. Each type
 * corresponds to a different Craft element type and has its own
 * default path and resolution patterns.
 *
 * @author Wabisoft
 * @since 6.4.0
 */
enum TemplateType: string
{
    case ENTRY = 'entry';
    case CATEGORY = 'category';
    case ITEM = 'item';
    case MATRIX = 'matrix';
    case PRODUCT = 'product';
    case ASSET = 'asset';

    /**
     * Returns the default path for this template type.
     *
     * Each template type has a default path that is used as the base
     * for template resolution when no custom path is provided.
     *
     * @return string The default path for this template type
     */
    public function getDefaultPath(): string
    {
        return '_' . $this->value;
    }



    /**
     * Creates a TemplateType from a string value.
     *
     * Provides a safe way to create TemplateType instances from string
     * values, with fallback to ENTRY for unknown values.
     *
     * @param string $value The string value to convert
     * @return self The corresponding TemplateType enum case
     */
    public static function fromString(string $value): self
    {
        return match ($value) {
            'entry' => self::ENTRY,
            'category' => self::CATEGORY,
            'item' => self::ITEM,
            'matrix' => self::MATRIX,
            'product' => self::PRODUCT,
            'asset' => self::ASSET,
            default => self::ENTRY,
        };
    }
}
