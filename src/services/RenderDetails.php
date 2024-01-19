<?php

namespace wabisoft\bonsaitwig\services;
use craft\base\Element;

use Craft;

class RenderDetails
{
    public static function blockDetails($block)
    {
        if(!self::shouldRender()) {
            return;
        }
        $output = Craft::$app->view->renderTemplate('_bonsai-twig/_partials/block-details.twig', ['block' => $block]);
        echo $output;
    }

    private static function shouldRender()
    {
        $isDev = Craft::$app->getConfig()->general->devMode;
        $showDebugInfo = Craft::$app->request->getQueryParam('showDebugInfo', false);

        if(!$isDev) {
            return false;
        }

        if($isDev && $showDebugInfo) {
            return true;
        }
        return false;
    }
}
