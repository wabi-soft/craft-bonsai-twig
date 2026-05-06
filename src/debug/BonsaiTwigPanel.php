<?php

namespace wabisoft\bonsaitwig\debug;

use Craft;
use yii\base\ViewContextInterface;
use yii\debug\Panel;

class BonsaiTwigPanel extends Panel implements ViewContextInterface
{
    public function getName(): string
    {
        return 'Bonsai Twig';
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
        return [
            'resolutions' => ResolutionCollector::getLog(),
            'droppedCount' => ResolutionCollector::getDroppedCount(),
            'beastmodeParam' => Craft::$app->getRequest()->getIsConsoleRequest()
                ? ''
                : (Craft::$app->getRequest()->getParam('beastmode') ?? ''),
            'hasCategories' => !empty(Craft::$app->categories->getAllGroups()),
            'hasCommerce' => Craft::$app->plugins->isPluginInstalled('commerce'),
        ];
    }
}
