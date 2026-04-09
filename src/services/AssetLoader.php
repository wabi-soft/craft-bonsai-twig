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
        }
        $prefixes[] = $path;

        // For each prefix, add paths in order of specificity
        foreach ($prefixes as $prefix) {
            // Most specific: volume/folder/filename
            if ($volume && $folderPath && $filename) {
                $checkTemplates[] = $prefix . '/' . $volume . '/' . $folderPath . '/' . $filename;
            }

            // Volume/folder with filename (no folder path)
            if ($volume && $filename && !$folderPath) {
                $checkTemplates[] = $prefix . '/' . $volume . '/' . $filename;
            }

            // Folder-level default
            if ($volume && $folderPath) {
                $checkTemplates[] = $prefix . '/' . $volume . '/' . $folderPath . '/default';
            }

            // Volume-level default
            if ($volume) {
                $checkTemplates[] = $prefix . '/' . $volume . '/default';
                $checkTemplates[] = $prefix . '/' . $volume;
            }

            // Global default for this prefix
            $checkTemplates[] = $prefix . '/default';
        }

        return HierarchyTemplateLoader::load(
            $checkTemplates,
            $variables,
            '',
            'asset'
        );
    }
}
