<?php

namespace wabisoft\bonsaitwig\enums;

/**
 * Simplified enum for debug modes in the Bonsai Twig plugin.
 *
 * This enum defines basic debug modes that can be enabled via the beastmode
 * parameter in development mode. Simplified to just enabled/disabled without
 * complex debug levels or performance monitoring modes.
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
     * that basic debug information should be displayed without
     * performance metrics or complex styling.
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
     * Simplified approach: any non-empty string enables basic debug mode.
     * No complex debug level parsing or validation needed for dev-only tool.
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
