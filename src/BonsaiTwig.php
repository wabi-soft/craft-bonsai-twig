<?php

namespace wabisoft\bonsaitwig;

use Craft;
use craft\base\Plugin;
use wabisoft\bonsaitwig\services\CategoryLoader;
use wabisoft\bonsaitwig\services\EntryLoader;
use wabisoft\bonsaitwig\services\HierarchyTemplateLoader;
use wabisoft\bonsaitwig\services\ItemLoader;
use wabisoft\bonsaitwig\services\MatrixLoader;
use wabisoft\bonsaitwig\web\twig\Templates;

/**
 * Bonsai Twig plugin
 *
 * @method static BonsaiTwig getInstance()
 * @property-read EntryLoader $entryLoader
 * @property-read CategoryLoader $categoryLoader
 * @property-read ItemLoader $itemLoader
 * @property-read MatrixLoader $matrixLoader
 * @property-read HierarchyTemplateLoader $hierarchyTemplateLoader
 */
class BonsaiTwig extends Plugin
{
    public string $schemaVersion = '1.0.0';

    public static function config(): array
    {
        return [
            'components' => ['entryLoader' => EntryLoader::class, 'categoryLoader' => CategoryLoader::class, 'itemLoader' => ItemLoader::class, 'matrixLoader' => MatrixLoader::class, 'hierarchyTemplateLoader' => HierarchyTemplateLoader::class],
        ];
    }

    public function init(): void
    {
        parent::init();

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
            // ...
        });
        Craft::$app->view->registerTwigExtension(new Templates());
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/4.x/extend/events.html to get started)
    }
}
