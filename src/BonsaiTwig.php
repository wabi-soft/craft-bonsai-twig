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
use craft\web\View;
use yii\base\Event;
use craft\events\RegisterTemplateRootsEvent;


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

        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
        });
        Craft::$app->view->registerTwigExtension(new Templates());
    }

    private function attachEventHandlers(): void
    {
        $isDev = Craft::$app->getConfig()->general->devMode;
        if ($isDev) {
            Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function (RegisterTemplateRootsEvent $event) {
                $event->roots['_bonsai-twig'] = __DIR__ . '/templates';
            });
        }
    }
}
