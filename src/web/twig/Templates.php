<?php

namespace wabisoft\bonsaitwig\web\twig;

use Craft;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use wabisoft\bonsaitwig\BonsaiTwig;

/**
 * Twig extension that provides hierarchical template loading functions.
 *
 * This extension registers Twig functions that allow templates to load other templates
 * based on hierarchical patterns. Each function corresponds to a different type of
 * Craft element and provides intelligent fallback mechanisms with proper dependency
 * injection for Craft 5 compatibility.
 *
 * @author Wabisoft
 * @since 6.4.0
 */
class Templates extends AbstractExtension
{
    /**
     * Returns an array of Twig functions provided by this extension.
     *
     * Registers four main template loading functions using proper dependency injection
     * to access services through the plugin instance:
     * - itemTemplates(): For general element template loading with context support
     * - entryTemplates(): For Craft entry-specific template loading
     * - categoryTemplates(): For Craft category-specific template loading
     * - matrixTemplates(): For Craft matrix block template loading
     * - btPath(): Returns current template path in devmode, empty string otherwise
     *
     * All functions are marked as HTML-safe since they return rendered template content.
     * Services are accessed through the plugin instance to ensure proper initialization.
     *
     * @return TwigFunction[] Array of Twig function definitions
     */
    public function getFunctions(): array
    {
        $plugin = BonsaiTwig::getInstance();

        return [
            new TwigFunction(
                'itemTemplates',
                [$plugin->itemLoader, 'load'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'entryTemplates',
                [$plugin->entryLoader, 'load'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'categoryTemplates',
                [$plugin->categoryLoader, 'load'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'matrixTemplates',
                [$plugin->matrixLoader, 'load'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'btPath',
                [$this, 'getTemplatePath'],
                ['needs_context' => true]
            ),
        ];
    }

    /**
     * Returns the template resolution hierarchy in devmode, empty string otherwise.
     *
     * This function provides a lightweight way to show all template paths that were
     * attempted during template resolution, similar to what the beastmode infobar shows.
     *
     * **PRODUCTION MODE**: Returns empty string immediately with zero overhead
     * **DEV MODE**: Returns formatted list of all attempted template paths with the
     *               resolved template marked with a checkmark (✓)
     *
     * The function name 'btPath' is short (Bonsai Twig Path) and unique enough to avoid
     * conflicts with other plugins or system functions.
     *
     * Usage in templates:
     * ```twig
     * <!-- {{ btPath() }} -->
     * ```
     *
     * Output example (in devmode):
     * ```
     * item-new/features/default/default
     * item-new/default/features
     * item-new/default/default ✓
     * item-new/features/default
     * item-new/features
     * item-new/default
     * ```
     *
     * @param array $context The Twig template context
     * @return string Template paths in devmode, empty string in production
     */
    public function getTemplatePath(array $context): string
    {
        // ============================================================
        // PRODUCTION MODE: Immediate early exit - zero overhead
        // ============================================================
        if (!Craft::$app->getConfig()->general->devMode) {
            return '';
        }

        // ============================================================
        // DEV MODE ONLY: Build template resolution hierarchy
        // ============================================================
        try {
            // Check if we have template resolution data stored in context
            if (!isset($context['_btTemplates']) || empty($context['_btTemplates'])) {
                // Fallback: try to get current template name from backtrace
                // This is only used when btPath() is called outside of Bonsai Twig template functions
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
                foreach ($trace as $frame) {
                    if (isset($frame['object']) && $frame['object'] instanceof \Twig\Template) {
                        return $frame['object']->getTemplateName();
                    }
                }
                return '';
            }

            // Format the template paths with resolved template marked
            $templates = $context['_btTemplates'];
            $resolvedTemplate = $context['_btResolvedTemplate'] ?? null;
            $output = [];

            foreach ($templates as $template) {
                // Mark the resolved template with a checkmark
                if ($resolvedTemplate && $template === $resolvedTemplate) {
                    $output[] = $template . ' ✓';
                } else {
                    $output[] = $template;
                }
            }

            return implode("\n", $output);
        } catch (\Throwable $e) {
            // Gracefully handle any errors without breaking template rendering
            return '';
        }
    }
}
