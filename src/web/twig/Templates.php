<?php

namespace wabisoft\bonsaitwig\web\twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use wabisoft\bonsaitwig\services\CategoryLoader;
use wabisoft\bonsaitwig\services\EntryLoader;
use wabisoft\bonsaitwig\services\ItemLoader;
use wabisoft\bonsaitwig\services\MatrixLoader;

/**
 * Twig extension that provides hierarchical template loading functions.
 *
 * This extension registers Twig functions that allow templates to load other templates
 * based on hierarchical patterns. Each function corresponds to a different type of
 * Craft element and provides intelligent fallback mechanisms.
 *
 * @author Wabisoft
 * @since 6.4.0
 */
class Templates extends AbstractExtension
{
    /**
     * Returns an array of Twig functions provided by this extension.
     *
     * Registers four main template loading functions:
     * - itemTemplates(): For general element template loading with context support
     * - entryTemplates(): For Craft entry-specific template loading
     * - categoryTemplates(): For Craft category-specific template loading
     * - matrixTemplates(): For Craft matrix block template loading
     *
     * All functions are marked as HTML-safe since they return rendered template content.
     *
     * @return TwigFunction[] Array of Twig function definitions
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'itemTemplates',
                [ItemLoader::class, 'load'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'entryTemplates',
                [EntryLoader::class, 'load'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'categoryTemplates',
                [CategoryLoader::class, 'load'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'matrixTemplates',
                [MatrixLoader::class, 'load'],
                ['is_safe' => ['html']]
            ),
        ];
    }
}
