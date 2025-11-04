<?php

namespace wabisoft\bonsaitwig;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\App;
use craft\web\View;

use wabisoft\bonsaitwig\models\Settings;
use wabisoft\bonsaitwig\services\CategoryLoader;
use wabisoft\bonsaitwig\services\EntryLoader;
use wabisoft\bonsaitwig\services\HierarchyTemplateLoader;
use wabisoft\bonsaitwig\services\ItemLoader;
use wabisoft\bonsaitwig\services\MatrixLoader;
use wabisoft\bonsaitwig\web\twig\Templates;
use yii\base\Event;

/**
 * Bonsai Twig plugin
 *
 * A development-only Craft CMS plugin that provides hierarchical template loading functions
 * for entries, categories, items, and matrix blocks. Simplified architecture focused on
 * core template resolution without performance monitoring or caching complexity.
 *
 * @author Wabisoft
 * @since 6.4.0
 * @method static BonsaiTwig getInstance()
 * @property-read EntryLoader $entryLoader Service for loading entry-based templates
 * @property-read CategoryLoader $categoryLoader Service for loading category-based templates
 * @property-read ItemLoader $itemLoader Service for loading item-based templates
 * @property-read MatrixLoader $matrixLoader Service for loading matrix block templates
 * @property-read HierarchyTemplateLoader $hierarchyTemplateLoader Core template resolution service
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
     * Registers only essential services for template loading functionality.
     * Simplified configuration without complex dependency injection.
     *
     * @return array<string, array<string, mixed>> Configuration array with service definitions
     */
    public static function config(): array
    {
        return [
            'components' => [
                'hierarchyTemplateLoader' => HierarchyTemplateLoader::class,
                'entryLoader' => EntryLoader::class,
                'categoryLoader' => CategoryLoader::class,
                'itemLoader' => ItemLoader::class,
                'matrixLoader' => MatrixLoader::class,
            ],
        ];
    }

    /**
     * Initializes the plugin and registers Twig extensions.
     *
     * Simplified initialization focused on development workflow support.
     * Registers the Twig extension and sets up basic development mode features
     * without complex dependency injection or performance monitoring.
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();

        // Basic Craft 5 compatibility check
        if (!version_compare(Craft::$app->getVersion(), '4.4.0', '>=')) {
            return; // Fail silently for unsupported versions
        }

        // Initialize services (on-demand through Yii)
        $this->initializeServices();

        // Register Twig extension
        Craft::$app->onInit(function(): void {
            $this->registerTwigExtension();
            $this->attachEventHandlers();
        });
    }



    /**
     * Initializes plugin services using direct instantiation.
     *
     * Services are initialized on-demand through Yii's component system.
     * No complex dependency injection required for simplified architecture.
     *
     * @return void
     */
    private function initializeServices(): void
    {
        // Services are initialized automatically by Yii when accessed
        // No manual initialization required for simplified architecture
    }

    /**
     * Registers the Twig extension that provides template loading functions.
     *
     * @return void
     */
    private function registerTwigExtension(): void
    {
        $twigExtension = new Templates();
        Craft::$app->view->registerTwigExtension($twigExtension);
    }

    /**
     * Attaches event handlers for development mode features.
     *
     * In development mode, registers additional template roots to allow
     * the plugin's debug templates to be accessible.
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
    }
}
