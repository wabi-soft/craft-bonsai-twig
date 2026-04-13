<?php

namespace wabisoft\bonsaitwig\services;

use craft\elements\Asset;
use wabisoft\bonsaitwig\BonsaiTwig;

/**
 * Service class for loading template paths based on Craft assets.
 *
 * Simplified service that provides basic hierarchical template path resolution
 * for Craft assets. Focuses on core functionality without complex validation
 * or optimization layers.
 *
 * Supports template resolution patterns like:
 * - asset/{volume}/{folder}/{filename}
 * - asset/{volume}/{folder}/default
 * - asset/{volume}/default
 * - asset/default
 *
 * @author Wabisoft
 * @since 6.4.0
 */
class AssetLoader
{
    /**
     * Loads and renders a template based on the provided Craft asset.
     *
     * @param array<string, mixed> $variables Configuration array containing:
     *        - asset: Required. Craft Asset element to base template paths on
     *        - path: Optional. Base path prefix (defaults to 'asset')
     *        - baseSite: Optional. Base site handle for multi-site support (defaults to false)
     *
     * @throws \InvalidArgumentException If asset is not a valid Craft Element
     * @return string The rendered template content
     */
    public static function load(array $variables = []): string
    {
        // Basic parameter validation
        $asset = $variables['asset'] ?? null;
        if (!$asset instanceof Asset) {
            throw new \InvalidArgumentException('Asset parameter is required and must be a valid Craft Asset element');
        }

        // Extract parameters with defaults
        $path = trim($variables['path'] ?? BonsaiTwig::getInstance()->getSettings()->getPathForType('asset'), '/');
        $baseSite = $variables['baseSite'] ?? false;
        if ($baseSite) {
            $baseSite = trim($baseSite, '/');
        }

        // Get asset properties for path building
        $volume = $asset->volume?->handle ?? '';

        // Get folder path - normalize slashes and trim
        $folderPath = '';
        if ($asset->getFolder()) {
            $folderPath = trim($asset->getFolder()->path ?? '', '/');
            $folderPath = str_replace('\\', '/', $folderPath);
        }

        // Get filename without extension for template matching
        $filename = $asset->filename ?? '';
        if ($filename) {
            $filename = pathinfo($filename, PATHINFO_FILENAME);
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

        if ($volume && $folderPath && $filename) {
            $addPath($volume . '/' . $folderPath . '/' . $filename);
        }
        if ($volume && $filename && !$folderPath) {
            $addPath($volume . '/' . $filename);
        }
        if ($volume && $folderPath) {
            $addPath($volume . '/' . $folderPath . '/default');
        }
        if ($volume) {
            $addPath($volume . '/default');
            $addPath($volume);
        }
        $addPath('default');

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            'asset'
        );
    }
}
