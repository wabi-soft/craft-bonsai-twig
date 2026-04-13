<?php

namespace wabisoft\bonsaitwig\web\twig;

use Craft;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use wabisoft\bonsaitwig\BonsaiTwig;

/**
 * Twig extension that provides hierarchical template loading functions.
 *
 * This extension registers Twig functions for development-focused template loading
 * based on hierarchical patterns. Simplified architecture without complex validation
 * or performance monitoring. Enhanced btPath() function returns complete HTML output
 * with styling, eliminating need for manual Twig wrapping.
 *
 * @author Wabisoft
 * @since 6.4.0
 */
class Templates extends AbstractExtension
{
    /**
     * Returns an array of Twig functions provided by this extension.
     *
     * Registers template loading functions for development workflow:
     * - itemTemplates(): For general element template loading with context support
     * - entryTemplates(): For Craft entry-specific template loading
     * - categoryTemplates(): For Craft category-specific template loading
     * - matrixTemplates(): For Craft matrix block template loading
     * - productTemplates(): For Craft Commerce product template loading
     * - assetTemplates(): For Craft asset template loading
     * - btPath(): Enhanced function that returns complete HTML debug output with styling
     *
     * All functions are marked as HTML-safe since they return rendered template content.
     * The enhanced btPath() function eliminates need for manual Twig wrapping by returning
     * complete HTML with CSS styling and template type context detection.
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
                'productTemplates',
                [$plugin->productLoader, 'load'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'assetTemplates',
                [$plugin->assetLoader, 'load'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'btPath',
                [$this, 'getTemplatePath'],
                ['needs_context' => true, 'is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Returns complete HTML debug output for template resolution in devmode, empty string otherwise.
     *
     * This function provides a complete HTML debug display showing template resolution
     * hierarchy with styling and context information. It automatically detects the template
     * type and shows appropriate headers and formatting.
     *
     * **PRODUCTION MODE**: Returns empty string immediately with zero overhead
     * **DEV MODE**: Returns complete HTML with CSS styling, template type context,
     *               and resolved template marked with checkmark (✓)
     *
     * The function name 'btPath' is short (Bonsai Twig Path) and unique enough to avoid
     * conflicts with other plugins or system functions.
     *
     * ## Usage Examples:
     *
     * ### Simple Usage (complete HTML output):
     * ```twig
     * {{ btPath() }}
     * ```
     *
     * ### HTML Comment (for DOM inspection):
     * ```twig
     * <!-- {{ btPath()|striptags }} -->
     * ```
     *
     * ### Conditional Display:
     * ```twig
     * {% if craft.app.config.general.devMode %}
     *     {{ btPath() }}
     * {% endif %}
     * ```
     *
     * ## Output Example (in devmode):
     * Complete HTML div with styling showing:
     * - Template type header (Matrix Default, Entry Template, etc.)
     * - List of attempted template paths
     * - Resolved template marked with ✓
     * - Clean, minimal styling
     *
     * The checkmark (✓) indicates which template was actually found and rendered.
     * This helps you understand the template resolution hierarchy and identify
     * exactly where to place your template override.
     *
     * @param array $context The Twig template context
     * @return string Complete HTML debug output in devmode, empty string in production
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
        // DEV MODE ONLY: Build complete HTML debug output
        // ============================================================
        try {
            // Check if we have template resolution data stored in context
            if (!isset($context['_btTemplates']) || empty($context['_btTemplates'])) {
                // Fallback: try to get current template name from backtrace
                // This is only used when btPath() is called outside of Bonsai Twig template functions
                // (e.g., in regular Craft templates not rendered through Bonsai Twig loaders)
                //
                // Note: Limited to 50 stack frames. In deeply nested template includes
                // (> 50 levels), the template may not be found. Increase this limit if needed.
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 50);
                foreach ($trace as $frame) {
                    if (isset($frame['object']) && $frame['object'] instanceof \Twig\Template) {
                        $templateName = $frame['object']->getTemplateName();
                        // Return complete HTML for single template
                        return $this->renderDebugOutput([$templateName], $templateName, 'Template');
                    }
                }
                return '';
            }

            // Get template data from context
            $templates = $context['_btTemplates'];
            $resolvedTemplate = $context['_btResolvedTemplate'] ?? null;

            // Detect template type from context or element
            $templateType = $this->detectTemplateType($context);

            // Append strategy to header when non-default
            $strategy = $context['_btStrategy'] ?? 'section';
            if ($strategy !== 'section') {
                $templateType .= ' [strategy: ' . $strategy . ']';
            }

            return $this->renderDebugOutput($templates, $resolvedTemplate, $templateType);
        } catch (\Throwable $e) {
            // Gracefully handle any errors without breaking template rendering
            \Craft::warning('btPath() error: ' . $e->getMessage(), __METHOD__);
            return '';
        }
    }

    /**
     * Renders the complete HTML debug output with styling and template information.
     *
     * @param array $templates Array of template paths that were attempted
     * @param string|null $resolvedTemplate The template that was actually found and used
     * @param string $templateType The type of template (Matrix Default, Entry Template, etc.)
     * @return string Complete HTML debug output
     */
    private static bool $debugCssRegistered = false;

    private function renderDebugOutput(array $templates, ?string $resolvedTemplate, string $templateType): string
    {
        // Register debug CSS once per page load
        if (!self::$debugCssRegistered) {
            Craft::$app->view->registerCss(
                '.bt-debug-output { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 12px; line-height: 1.4; background: #1e1e1e; color: #d4d4d4; border: 1px solid #454545; border-radius: 6px; padding: 12px; margin: 8px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.2); max-width: 600px; } '
                . '.bt-debug-output .bt-debug-header { color: #569cd6; font-weight: 600; font-size: 13px; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 1px solid #454545; } '
                . '.bt-debug-output .bt-debug-list { margin: 0; padding: 0; list-style: none; } '
                . '.bt-debug-output .bt-debug-item { padding: 3px 0 3px 16px; position: relative; font-family: "SF Mono", Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 11px; } '
                . '.bt-debug-output .bt-debug-item::before { content: "\2192"; position: absolute; left: 0; color: #808080; font-weight: bold; } '
                . '.bt-debug-output .bt-debug-item--resolved { color: #4ec9b0; font-weight: 600; } '
                . '.bt-debug-output .bt-debug-item--resolved::before { content: "\2713"; color: #4ec9b0; } '
                . '.bt-debug-output .bt-debug-item--missing { color: #808080; }'
            );
            self::$debugCssRegistered = true;
        }

        $html = '<div class="bt-debug-output">';
        $html .= '<div class="bt-debug-header">' . htmlspecialchars($templateType) . '</div>';
        $html .= '<ul class="bt-debug-list">';

        foreach ($templates as $template) {
            $isResolved = $resolvedTemplate && $template === $resolvedTemplate;
            $cssClass = $isResolved ? 'bt-debug-item--resolved' : 'bt-debug-item--missing';
            $html .= '<li class="bt-debug-item ' . $cssClass . '">';
            $html .= htmlspecialchars($template);
            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Detects the template type from the Twig context and returns appropriate header.
     *
     * Automatically detects template type from calling context to show appropriate
     * headers like "Matrix Default", "Entry Template", "Category Template", etc.
     * Uses element inspection and context variables to determine the most specific
     * template type description.
     *
     * @param array $context The Twig template context
     * @return string The detected template type for display header
     */
    private function detectTemplateType(array $context): string
    {
        // Check for matrix block context first (highest priority)
        if (isset($context['block'])) {
            $block = $context['block'];
            if ($block instanceof \craft\elements\MatrixBlock || $block instanceof \craft\elements\Entry) {
                // Get block type handle for more specific header
                $blockType = null;
                if ($block instanceof \craft\elements\MatrixBlock) {
                    $blockType = $block->type?->handle ?? null;
                } elseif ($block instanceof \craft\elements\Entry) {
                    $blockType = $block->type?->handle ?? null;
                }
                
                if ($blockType) {
                    return 'Matrix Block: ' . ucfirst($blockType);
                }
                return 'Matrix Default';
            }
        }

        // Legacy matrix block context check
        if (isset($context['matrixBlock'])) {
            return 'Matrix Default';
        }

        // Check for entry context
        if (isset($context['entry'])) {
            $entry = $context['entry'];
            if ($entry instanceof \craft\base\Element) {
                if ($entry instanceof \craft\elements\Category) {
                    // Get category group for more specific header
                    $groupHandle = $entry->group?->handle ?? null;
                    if ($groupHandle) {
                        return 'Category Template: ' . ucfirst($groupHandle);
                    }
                    return 'Category Template';
                } elseif ($entry instanceof \craft\elements\Entry) {
                    // Get entry section and type for more specific header
                    $sectionHandle = $entry->section?->handle ?? null;
                    $typeHandle = $entry->type?->handle ?? null;
                    
                    if ($sectionHandle && $typeHandle && $sectionHandle !== $typeHandle) {
                        return 'Entry Template: ' . ucfirst($sectionHandle) . ' (' . ucfirst($typeHandle) . ')';
                    } elseif ($sectionHandle) {
                        return 'Entry Template: ' . ucfirst($sectionHandle);
                    } elseif ($typeHandle) {
                        return 'Entry Template: ' . ucfirst($typeHandle);
                    }
                    return 'Entry Template';
                }
            }
            return 'Entry Template';
        }

        // Check for category context (direct category variable)
        if (isset($context['category'])) {
            $category = $context['category'];
            if ($category instanceof \craft\elements\Category) {
                $groupHandle = $category->group?->handle ?? null;
                if ($groupHandle) {
                    return 'Category Template: ' . ucfirst($groupHandle);
                }
            }
            return 'Category Template';
        }

        // Check for product context (Commerce product variable)
        if (isset($context['product'])) {
            $product = $context['product'];
            if ($product instanceof \craft\base\Element) {
                // Check if it's a Commerce Product (without hard dependency)
                if (method_exists($product, 'getType') && $product->type) {
                    $productType = $product->type?->handle ?? null;
                    if ($productType) {
                        return 'Product Template: ' . ucfirst($productType);
                    }
                }
            }
            return 'Product Template';
        }

        // Check for item context (generic element)
        if (isset($context['item'])) {
            $item = $context['item'];
            if ($item instanceof \craft\base\Element) {
                // Try to determine element type for more specific header
                if ($item instanceof \craft\elements\Entry) {
                    $sectionHandle = $item->section?->handle ?? null;
                    if ($sectionHandle) {
                        return 'Item Template: ' . ucfirst($sectionHandle);
                    }
                    return 'Item Template: Entry';
                } elseif ($item instanceof \craft\elements\Category) {
                    $groupHandle = $item->group?->handle ?? null;
                    if ($groupHandle) {
                        return 'Item Template: ' . ucfirst($groupHandle);
                    }
                    return 'Item Template: Category';
                } elseif ($item instanceof \craft\elements\Asset) {
                    return 'Item Template: Asset';
                } elseif ($item instanceof \craft\elements\User) {
                    return 'Item Template: User';
                }
            }
            return 'Item Template';
        }

        // Check for asset context (direct asset variable)
        if (isset($context['asset'])) {
            $asset = $context['asset'];
            if ($asset instanceof \craft\elements\Asset) {
                $volumeHandle = $asset->volume?->handle ?? null;
                if ($volumeHandle) {
                    return 'Asset Template: ' . ucfirst($volumeHandle);
                }
            }
            return 'Asset Template';
        }

        if (isset($context['user'])) {
            return 'User Template';
        }

        // Check for global context
        if (isset($context['global'])) {
            return 'Global Template';
        }

        // Default fallback
        return 'Template';
    }
}
