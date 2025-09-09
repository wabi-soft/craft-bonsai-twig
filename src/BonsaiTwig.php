<?php

namespace wabisoft\bonsaitwig;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use craft\events\ModelEvent;
use craft\base\Element;
use craft\helpers\App;
use wabisoft\bonsaitwig\services\CategoryLoader;
use wabisoft\bonsaitwig\services\EntryLoader;
use wabisoft\bonsaitwig\services\HierarchyTemplateLoader;
use wabisoft\bonsaitwig\services\ItemLoader;
use wabisoft\bonsaitwig\services\MatrixLoader;
use wabisoft\bonsaitwig\services\CacheService;
use wabisoft\bonsaitwig\services\PerformanceMonitor;
use wabisoft\bonsaitwig\services\ErrorReportingService;
use wabisoft\bonsaitwig\web\twig\Templates;
use wabisoft\bonsaitwig\models\Settings;
use wabisoft\bonsaitwig\exceptions\BonsaiTwigException;
use yii\base\Event;
use yii\base\InvalidConfigException;

/**
 * Bonsai Twig plugin
 *
 * A Craft CMS plugin that provides hierarchical template loading functions for entries,
 * categories, items, and matrix blocks. Supports development mode debugging and
 * multi-site template resolution.
 *
 * @author Wabisoft
 * @since 6.4.0
 * @method static BonsaiTwig getInstance()
 * @property-read EntryLoader $entryLoader Service for loading entry-based templates
 * @property-read CategoryLoader $categoryLoader Service for loading category-based templates
 * @property-read ItemLoader $itemLoader Service for loading item-based templates
 * @property-read MatrixLoader $matrixLoader Service for loading matrix block templates
 * @property-read HierarchyTemplateLoader $hierarchyTemplateLoader Core template resolution service
 * @property-read CacheService $cacheService Enhanced caching service for performance optimization
 * @property-read PerformanceMonitor $performanceMonitor Performance monitoring service for development mode
 * @property-read ErrorReportingService $errorReportingService Comprehensive error reporting and debugging service
 */
class BonsaiTwig extends Plugin
{
    /**
     * @var string The plugin's schema version
     */
    public string $schemaVersion = '1.0.0';

    /**
     * @var bool Whether the plugin has a CP section
     */
    public bool $hasCpSection = false;

    /**
     * @var bool Whether the plugin has a CP settings page
     */
    public bool $hasCpSettings = true;

    /**
     * Creates and returns the model used to store the plugin's settings.
     *
     * @return Settings The plugin settings model
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate('_bonsai-twig/settings', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    /**
     * Returns the plugin's configuration array for service registration.
     *
     * Defines all the service components that will be available through the plugin instance.
     * These services handle different types of template loading and resolution with proper
     * dependency injection and type hints for Craft 5 compatibility.
     *
     * @return array<string, array<string, mixed>> Configuration array with service definitions
     */
    public static function config(): array
    {
        return [
            'components' => [
                'cacheService' => [
                    'class' => CacheService::class,
                ],
                'performanceMonitor' => [
                    'class' => PerformanceMonitor::class,
                ],
                'errorReportingService' => [
                    'class' => ErrorReportingService::class,
                ],
                'hierarchyTemplateLoader' => [
                    'class' => HierarchyTemplateLoader::class,
                ],
                'entryLoader' => [
                    'class' => EntryLoader::class,
                ],
                'categoryLoader' => [
                    'class' => CategoryLoader::class,
                ],
                'itemLoader' => [
                    'class' => ItemLoader::class,
                ],
                'matrixLoader' => [
                    'class' => MatrixLoader::class,
                ],
            ],
        ];
    }

    /**
     * Initializes the plugin and registers Twig extensions.
     *
     * Sets up the plugin by validating configuration, registering the Twig extension
     * that provides template loading functions, and attaching event handlers for
     * development mode features with proper error handling.
     *
     * @return void
     * @throws InvalidConfigException If plugin configuration is invalid
     * @throws BonsaiTwigException If plugin initialization fails
     */
    public function init(): void
    {
        parent::init();

        try {
            // Validate plugin configuration
            $this->validateConfiguration();

            // Initialize services with dependency injection
            $this->initializeServices();

            // Register Twig extension after services are ready
            Craft::$app->onInit(function(): void {
                $this->registerTwigExtension();
                $this->attachEventHandlers();
            });

        } catch (\Throwable $e) {
            throw new BonsaiTwigException(
                'Failed to initialize Bonsai Twig plugin: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Validates the plugin configuration to ensure all required components are available.
     *
     * Performs comprehensive validation of the plugin's configuration including
     * service dependencies, Craft version compatibility, and PHP version requirements.
     *
     * @return void
     * @throws InvalidConfigException If configuration validation fails
     */
    private function validateConfiguration(): void
    {
        // Validate Craft CMS version compatibility
        if (!version_compare(Craft::$app->getVersion(), '5.0.0', '>=')) {
            throw new InvalidConfigException('Bonsai Twig requires Craft CMS 5.0.0 or higher');
        }

        // Validate PHP version compatibility
        if (!version_compare(PHP_VERSION, '8.2.0', '>=')) {
            throw new InvalidConfigException('Bonsai Twig requires PHP 8.2.0 or higher');
        }

        // Validate required Craft components are available
        $requiredComponents = ['view', 'cache', 'config'];
        foreach ($requiredComponents as $component) {
            if (!Craft::$app->has($component)) {
                throw new InvalidConfigException("Required Craft component '{$component}' is not available");
            }
        }
    }

    /**
     * Initializes plugin services with proper dependency injection.
     *
     * Sets up all plugin services in the correct order to ensure proper dependency
     * resolution. Core services are initialized first, followed by template loaders
     * that depend on them.
     *
     * @return void
     * @throws InvalidConfigException If service initialization fails
     */
    private function initializeServices(): void
    {
        try {
            // Initialize core services first (no dependencies)
            $this->get('cacheService');
            $this->get('performanceMonitor');
            $this->get('errorReportingService');

            // Initialize hierarchy loader (depends on core services)
            $this->get('hierarchyTemplateLoader');

            // Initialize template loaders (depend on hierarchy loader)
            $this->get('entryLoader');
            $this->get('categoryLoader');
            $this->get('itemLoader');
            $this->get('matrixLoader');

        } catch (\Throwable $e) {
            throw new InvalidConfigException(
                'Failed to initialize plugin services: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Registers the Twig extension with proper error handling.
     *
     * Registers the Templates extension that provides the template loading functions
     * to the Twig environment with comprehensive error handling and validation.
     *
     * @return void
     * @throws BonsaiTwigException If Twig extension registration fails
     */
    private function registerTwigExtension(): void
    {
        try {
            $twigExtension = new Templates();
            Craft::$app->view->registerTwigExtension($twigExtension);
        } catch (\Throwable $e) {
            throw new BonsaiTwigException(
                'Failed to register Twig extension: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Attaches event handlers for development mode features and cache invalidation.
     *
     * In development mode, registers additional template roots to allow
     * the plugin's debug templates to be accessible. Also sets up cache
     * invalidation handlers for element changes.
     *
     * @return void
     */
    private function attachEventHandlers(): void
    {
         // Normalize CRAFT_DEV_MODE to a boolean if set, otherwise fall back to config
         $envDev = App::env('CRAFT_DEV_MODE');
         $isDev = $envDev !== null
             ? filter_var((string)$envDev, FILTER_VALIDATE_BOOLEAN)
             : Craft::$app->getConfig()->general->devMode;
         
         if ($isDev) {
            Event::on(
                View::class,
                View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
                function(RegisterTemplateRootsEvent $event): void {
                    $event->roots['_bonsai-twig'] = __DIR__ . '/templates';
                }
            );
        }

        // Cache invalidation event handlers with error handling
        Event::on(
            Element::class,
            Element::EVENT_AFTER_SAVE,
            function(ModelEvent $event): void {
                try {
                    /** @var Element $element */
                    $element = $event->sender;
                    $this->cacheService->invalidateElementCache($element);
                } catch (\Throwable $e) {
                    $this->errorReportingService->logError(
                        'Cache invalidation failed after element save',
                        $e,
                        ['elementId' => $element->id ?? 'unknown']
                    );
                }
            }
        );

        Event::on(
            Element::class,
            Element::EVENT_AFTER_DELETE,
            function(ModelEvent $event): void {
                try {
                    /** @var Element $element */
                    $element = $event->sender;
                    $this->cacheService->invalidateElementCache($element);
                } catch (\Throwable $e) {
                    $this->errorReportingService->logError(
                        'Cache invalidation failed after element delete',
                        $e,
                        ['elementId' => $element->id ?? 'unknown']
                    );
                }
            }
        );
    }
}
