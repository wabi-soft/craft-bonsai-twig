<?php

namespace wabisoft\bonsaitwig\services;

use craft\base\Element;
use wabisoft\bonsaitwig\BonsaiTwig;
use wabisoft\bonsaitwig\enums\Strategy;

/**
 * Service class for loading template paths based on Craft elements and context.
 *
 * Simplified service that provides basic hierarchical template path resolution
 * for development use. Supports style variations and context awareness without
 * complex validation or optimization layers.
 *
 * @author Wabisoft
 * @since 6.4.0
 */
class ItemLoader
{
    /**
     * Loads and renders a template based on the provided element.
     *
     * @param array<string, mixed> $variables Configuration array containing:
     *        - entry: Required. Craft Element to base template paths on
     *        - path: Optional. Base path prefix (defaults to 'item')
     *        - style: Optional. Style variation name for template customization
     *        - ctx: Optional. Context Element for additional path variations
     *        - default: Optional. Default template name (defaults to 'default')
     *        - ctxPath: Optional. Context path segment (defaults to 'ctx')
     *        - baseSite: Optional. Base site handle for multi-site support (defaults to false)
     *
     * @throws \InvalidArgumentException If entry is not a valid Craft Element
     * @return string The rendered template content
     */
    public static function load(array $variables = []): string
    {
        // Basic parameter validation
        $entry = $variables['entry'] ?? null;
        if (!$entry instanceof Element) {
            throw new \InvalidArgumentException('Entry parameter is required and must be a valid Craft Element');
        }
        
        // Extract parameters with defaults
        $settings = BonsaiTwig::getInstance()->getSettings();
        $path = trim($variables['path'] ?? $settings->getPathForType('item'), '/');
        $style = $variables['style'] ?? null;
        $ctx = $variables['ctx'] ?? null;
        $default = $variables['default'] ?? 'default';
        $ctxPath = trim($variables['ctxPath'] ?? 'ctx', '/');
        $baseSite = $variables['baseSite'] ?? false;
        if ($baseSite) {
            $baseSite = trim($baseSite, '/');
        }

        // Get element properties for path building
        $section = $entry->section?->handle ?? $entry->group?->handle ?? '';
        $type = $entry->type?->handle ?? false;
        $slug = $entry->slug ?? '';

        // Resolve strategy: per-template > config/CP > default
        $strategy = Strategy::tryFrom($variables['strategy'] ?? $settings->strategy ?? '') ?? Strategy::SECTION;
        if ($strategy === Strategy::TYPE && $type) {
            [$section, $type] = [$type, $section];
        }

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

        // Add paths interleaved by specificity: all prefixes per level before dropping down
        $addPath = function (string $templatePath) use (&$checkTemplates, $prefixes) {
            foreach ($prefixes as $prefix) {
                $candidate = $prefix . '/' . $templatePath;
                if (!in_array($candidate, $checkTemplates)) {
                    $checkTemplates[] = $candidate;
                }
            }
        };

        // Context-specific paths
        if ($ctx) {
            $contextSection = $ctx->section?->handle ?? $ctx->group?->handle ?? '';
            $contextType = $ctx->type?->handle ?? '';

            if ($strategy === Strategy::TYPE && $contextType !== '') {
                [$contextSection, $contextType] = [$contextType, $contextSection];
            }

            if ($style && $contextSection && $contextType) {
                $addPath($section . '/' . $ctxPath . '/' . $contextSection . '/' . $contextType . '/' . $style);
            }
            if ($type && $contextSection && $contextType) {
                $addPath($section . '/' . $ctxPath . '/' . $contextSection . '/' . $contextType . '/' . $type);
                $addPath($section . '/' . $ctxPath . '/' . $contextSection . '/' . $contextType . '/' . $default);
            }
            if ($style && $contextSection) {
                $addPath($section . '/' . $ctxPath . '/' . $contextSection . '/' . $style);
            }
            if ($type && $contextSection) {
                $addPath($section . '/' . $ctxPath . '/' . $contextSection . '/' . $type);
            }
            if ($contextSection) {
                $addPath($section . '/' . $ctxPath . '/' . $contextSection . '/' . $default);
                $addPath($section . '/' . $ctxPath . '/' . $contextSection);
            }
        }

        // Non-context template paths
        if ($style) {
            if ($type) {
                $addPath($section . '/' . $type . '/' . $style);
            }
            $addPath($section . '/' . $style);
            $addPath($style);
        }

        if ($type) {
            $addPath($section . '/' . $type . '/' . $slug);
            $addPath($section . '/' . $type);
            $addPath($section . '/' . $type . '/' . $default);
        } else {
            if (!empty($slug)) {
                $addPath($section . '/' . $slug);
            }
        }

        // General fallback paths
        $addPath($section . '/' . $default);
        $addPath($section);
        $addPath('default');

        // Pass strategy to debug pipeline (devMode only to avoid leaking into template scope)
        if (\Craft::$app->getConfig()->general->devMode) {
            $variables['_btStrategy'] = $strategy->value;
        }

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            'item'
        );
    }
}
