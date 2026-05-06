<?php

namespace wabisoft\bonsaitwig\debug;

use Craft;
use yii\base\ViewContextInterface;
use yii\debug\Panel;

class BonsaiTwigPanel extends Panel implements ViewContextInterface
{
    public function getName(): string
    {
        return 'TPL Routing';
    }

    public function getViewPath(): string
    {
        return __DIR__ . '/views';
    }

    public function getSummary(): string
    {
        $count = count($this->data['resolutions'] ?? []);

        return Craft::$app->getView()->render('summary', [
            'panel' => $this,
            'count' => $count,
        ], $this);
    }

    public function getDetail(): string
    {
        return Craft::$app->getView()->render('detail', [
            'panel' => $this,
        ], $this);
    }

    /** @return array<string, mixed> */
    public function save(): array
    {
        $request = Craft::$app->getRequest();
        $isConsole = $request->getIsConsoleRequest();

        $siteHandle = null;
        try {
            $siteHandle = Craft::$app->getSites()->getCurrentSite()->handle;
        } catch (\Throwable) {
        }

        return [
            'resolutions' => ResolutionCollector::getLog(),
            'droppedCount' => ResolutionCollector::getDroppedCount(),
            'beastmodeParam' => $isConsole ? '' : ($request->getParam('beastmode') ?? ''),
            'pageUrl' => $isConsole ? '' : $request->getUrl(),
            'siteHandle' => $siteHandle,
            'hasCategories' => !empty(Craft::$app->categories->getAllGroups()),
            'hasCommerce' => Craft::$app->plugins->isPluginInstalled('commerce'),
        ];
    }
}
