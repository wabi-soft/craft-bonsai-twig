<?php

namespace wabisoft\bonsaitwig\enums;

/**
 * Template resolution strategy.
 *
 * Controls whether template paths are organized with section or entry type
 * as the primary folder in the hierarchy.
 *
 * @author Wabisoft
 * @since 8.0.0
 */
enum Strategy: string
{
    case SECTION = 'section';
    case TYPE = 'type';
}
