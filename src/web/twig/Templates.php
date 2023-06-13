<?php

namespace wabisoft\bonsaitwig\web\twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use wabisoft\bonsaitwig\services\CategoryLoader;
use wabisoft\bonsaitwig\services\EntryLoader;
use wabisoft\bonsaitwig\services\ItemLoader;
use wabisoft\bonsaitwig\services\MatrixLoader;

/**
 * Twig extension
 */
class Templates extends AbstractExtension
{

    public function getFunctions(): array
    {
        return [

            new TwigFunction(
                'itemTemplates',
                [
                    ItemLoader::class,
                    'load'
                ],
                [
                    'is_safe' => [
                        'html',
                    ]
                ]
            ),
            new TwigFunction(
                'entryTemplates',
                [
                    EntryLoader::class,
                    'load'
                ],
                [
                    'is_safe' => [
                        'html',
                    ]
                ]
            ),
            new TwigFunction(
                'categoryTemplates',
                [
                    CategoryLoader::class,
                    'load'
                ],
                [
                    'is_safe' => [
                        'html',
                    ]
                ]
            ),
            new TwigFunction(
                'matrixTemplates',
                [
                    MatrixLoader::class,
                    'load'
                ],
                [
                    'is_safe' => [
                        'html',
                    ]
                ]
            )
        ];
    }

}
