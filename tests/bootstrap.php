<?php

/**
 * Test bootstrap file for Bonsai Twig plugin tests.
 *
 * Sets up the testing environment, loads dependencies, and configures
 * mock objects for Craft CMS components.
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Set up test environment constants
defined('CRAFT_ENVIRONMENT') || define('CRAFT_ENVIRONMENT', 'test');
defined('CRAFT_DEV_MODE') || define('CRAFT_DEV_MODE', true);

// Initialize Mockery for test cleanup
if (class_exists('Mockery')) {
    // Register Mockery close handler for PHPUnit
    register_shutdown_function(function() {
        if (class_exists('Mockery')) {
            Mockery::close();
        }
    });
}