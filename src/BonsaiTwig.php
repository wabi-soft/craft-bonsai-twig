<?php

namespace wabisoft\bonsaitwig;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
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
 */
class BonsaiTwig extends Plugin
{
    /**
     * @var string The plugin's schema version
     */
    public string $schemaVersion = '1.0.0';

    /**
     * Returns the plugin's configuration array for service registration.
     *
     * Defines all the service components that will be available through the plugin instance.
     * These services handle different types of template loading and resolution.
     *
     * @return array<string, class-string> Configuration array mapping service names to class names
     */
    public static function config(): array
    {
        return [
            'components' => [
                'entryLoader' => EntryLoader::class,
                'categoryLoader' => CategoryLoader::class,
                'itemLoader' => ItemLoader::class,
                'matrixLoader' => MatrixLoader::class,
                'hierarchyTemplateLoader' => HierarchyTemplateLoader::class,
            ],
        ];
    }

    /**
     * Initializes the plugin and registers Twig extensions.
     *
     * Sets up the plugin by registering the Twig extension that provides template
     * loading functions and attaching event handlers for development mode features.
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();

        Craft::$app->onInit(function(): void {
            $this->attachEventHandlers();
        });
        Craft::$app->view->registerTwigExtension(new Templates());
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
        $isDev = Craft::$app->getConfig()->general->devMode;
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
