<?php

namespace wabisoft\bonsaitwig\services;

use craft\base\Element;
use wabisoft\bonsaitwig\BonsaiTwig;

/**
 * Service class for loading template paths based on Craft Commerce products.
 *
 * Simplified service that provides basic hierarchical template path resolution
 * for Craft Commerce products. Focuses on core functionality without complex
 * validation or optimization layers.
 *
 * Supports template resolution patterns like:
 * - product/{productType}/{slug}
 * - product/{productType}/default
 * - product/{productType}
 * - product/default
 *
 * @author Wabisoft
 * @since 6.4.0
 */
class ProductLoader
{
    /**
     * Loads and renders a template based on the provided Commerce product.
     *
     * @param array<string, mixed> $variables Configuration array containing:
     *        - product: Required. Craft Commerce Product element to base template paths on
     *        - path: Optional. Base path prefix (defaults to 'product')
     *        - baseSite: Optional. Base site handle for multi-site support (defaults to false)
     *
     * @throws \InvalidArgumentException If product is not a valid Craft Element
     * @return string The rendered template content
     */
    public static function load(array $variables = []): string
    {
        // Basic parameter validation
        $product = $variables['product'] ?? null;
        if (!$product instanceof Element) {
            throw new \InvalidArgumentException('Product parameter is required and must be a valid Craft Element');
        }

        // Extract parameters with defaults
        $path = trim($variables['path'] ?? BonsaiTwig::getInstance()->getSettings()->getPathForType('product'), '/');
        $baseSite = $variables['baseSite'] ?? false;

        // Get product properties for path building
        $productType = $product->type?->handle ?? '';
        $slug = $product->slug ?? '';

        // Build template paths in order of specificity
        $checkTemplates = [];

        // Build prefixes: $baseSite/$path (site-first) and $path (global)
        $prefixes = [];
        if ($baseSite) {
            $prefixes[] = $baseSite . '/' . $path;
            $primarySite = \Craft::$app->sites->primarySite->handle;
            if ($baseSite !== $primarySite) {
                $prefixes[] = $primarySite . '/' . $path;
            }
        }
        $prefixes[] = $path;

        foreach ($prefixes as $prefix) {
            $checkTemplates[] = $prefix . '/' . $productType . '/' . $slug;
            $checkTemplates[] = $prefix . '/' . $productType . '/default';
            $checkTemplates[] = $prefix . '/' . $productType;
            $checkTemplates[] = $prefix . '/default';
        }

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            'product'
        );
    }
}
