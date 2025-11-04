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
    case ENABLED = 'enabled';

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

        // Any non-empty string enables debug mode
        return $value !== '' ? self::ENABLED : self::DISABLED;
    }


}
