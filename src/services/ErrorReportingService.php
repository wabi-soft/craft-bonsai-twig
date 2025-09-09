<?php

namespace wabisoft\bonsaitwig\services;

use Craft;
use craft\base\Element;
use wabisoft\bonsaitwig\enums\TemplateType;
use wabisoft\bonsaitwig\exceptions\TemplateNotFoundException;
use wabisoft\bonsaitwig\exceptions\InvalidElementException;
use wabisoft\bonsaitwig\valueobjects\TemplateContext;
use yii\base\Component;

/**
 * Error reporting service for comprehensive debugging and error handling.
 *
 * This service provides detailed error messages with attempted template paths,
 * context information for missing template variables, and helpful debugging
 * suggestions for common issues.
 *
 * Features:
 * - Detailed error messages with full context
 * - Template path analysis and suggestions
 * - Common issue detection and resolution hints
 * - Development vs production error handling
 * - Error categorization and severity assessment
 * - Debugging suggestions based on error patterns
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\services
 * @since 6.4.0
 */
class ErrorReportingService extends Component
{
    /**
     * Common template resolution issues and their solutions.
     *
     * @var array<string, array<string, string>>
     */
    private const COMMON_ISSUES = [
        'missing_section_template' => [
            'pattern' => '/entry\/[^\/]+\.twig$/',
            'title' => 'Missing Section Template',
            'description' => 'The section-specific template could not be found.',
            'suggestions' => [
                'Create a template file matching the section handle',
                'Check that the section handle matches the template filename',
                'Ensure the template is in the correct directory',
                'Verify file permissions allow reading the template',
            ],
        ],
        'missing_entry_type_template' => [
            'pattern' => '/entry\/[^\/]+\/[^\/]+\.twig$/',
            'title' => 'Missing Entry Type Template',
            'description' => 'The entry type-specific template could not be found.',
            'suggestions' => [
                'Create a template file matching the entry type handle',
                'Check that the entry type handle matches the template filename',
                'Consider creating a fallback section template',
                'Verify the entry type exists and is enabled',
            ],
        ],
        'missing_category_group_template' => [
            'pattern' => '/category\/[^\/]+\.twig$/',
            'title' => 'Missing Category Group Template',
            'description' => 'The category group-specific template could not be found.',
            'suggestions' => [
                'Create a template file matching the category group handle',
                'Check that the category group handle matches the template filename',
                'Ensure the template is in the correct directory',
                'Verify the category group exists and is enabled',
            ],
        ],
        'missing_matrix_block_template' => [
            'pattern' => '/matrix\/[^\/]+\.twig$/',
            'title' => 'Missing Matrix Block Template',
            'description' => 'The matrix block type template could not be found.',
            'suggestions' => [
                'Create a template file matching the matrix block type handle',
                'Check that the block type handle matches the template filename',
                'Consider creating a fallback matrix template',
                'Verify the matrix block type exists and is enabled',
            ],
        ],
        'site_specific_template_missing' => [
            'pattern' => '/\/_[^\/]+\//',
            'title' => 'Missing Site-Specific Template',
            'description' => 'The site-specific template variation could not be found.',
            'suggestions' => [
                'Create site-specific template variations in _siteHandle directories',
                'Check that the site handle matches the directory name',
                'Consider using the primary site template as a fallback',
                'Verify multi-site configuration is correct',
            ],
        ],
        'template_directory_missing' => [
            'pattern' => '/^[^\/]+$/',
            'title' => 'Template Directory Not Found',
            'description' => 'The base template directory could not be found.',
            'suggestions' => [
                'Create the required template directory structure',
                'Check template path configuration',
                'Verify Craft template directory settings',
                'Ensure proper file permissions on template directories',
            ],
        ],
    ];

    /**
     * Generates a comprehensive error report for template resolution failures.
     *
     * @param TemplateNotFoundException $exception The template not found exception
     * @param TemplateContext|null $context Optional template context for additional info
     * @param array<string, mixed> $debugData Additional debug data
     * @return array<string, mixed> Comprehensive error report
     */
    public function generateTemplateNotFoundReport(
        TemplateNotFoundException $exception,
        ?TemplateContext $context = null,
        array $debugData = []
    ): array {
        $attemptedPaths = $exception->getAttemptedPaths();
        $templateType = $exception->getTemplateType();
        
        $report = [
            'error_type' => 'template_not_found',
            'template_type' => $templateType->value,
            'attempted_paths' => $attemptedPaths,
            'path_count' => count($attemptedPaths),
            'primary_message' => $exception->getMessage(),
            'timestamp' => date('c'),
            'context' => $this->extractContextInfo($context),
            'path_analysis' => $this->analyzeAttemptedPaths($attemptedPaths, $templateType),
            'common_issues' => $this->identifyCommonIssues($attemptedPaths),
            'suggestions' => $this->generateSuggestions($attemptedPaths, $templateType, $context),
            'debug_info' => $debugData,
        ];

        // Add element-specific information if available
        if ($context && $context->element) {
            $report['element_info'] = $this->extractElementInfo($context->element);
        }

        // Add site-specific information for multi-site setups
        if (Craft::$app->getIsMultiSite()) {
            $report['site_info'] = $this->extractSiteInfo($context);
        }

        return $report;
    }

    /**
     * Generates a comprehensive error report for invalid element exceptions.
     *
     * @param InvalidElementException $exception The invalid element exception
     * @param mixed $providedValue The value that was provided
     * @param array<string, mixed> $debugData Additional debug data
     * @return array<string, mixed> Comprehensive error report
     */
    public function generateInvalidElementReport(
        InvalidElementException $exception,
        mixed $providedValue,
        array $debugData = []
    ): array {
        return [
            'error_type' => 'invalid_element',
            'expected_type' => $exception->expectedType,
            'actual_type' => get_debug_type($providedValue),
            'actual_value' => $this->sanitizeValueForLogging($providedValue),
            'primary_message' => $exception->getMessage(),
            'timestamp' => date('c'),
            'suggestions' => $this->generateElementValidationSuggestions($exception->expectedType, $providedValue),
            'debug_info' => $debugData,
        ];
    }

    /**
     * Formats an error report for display in development mode.
     *
     * @param array<string, mixed> $errorReport The error report to format
     * @return string Formatted error message for display
     */
    public function formatErrorForDisplay(array $errorReport): string
    {
        $output = [];
        
        // Header
        $output[] = "🚨 Bonsai Twig Error Report";
        $output[] = str_repeat("=", 50);
        
        // Primary error information
        $output[] = "Error Type: " . ($errorReport['error_type'] ?? 'unknown');
        $output[] = "Message: " . ($errorReport['primary_message'] ?? 'No message available');
        $output[] = "Timestamp: " . ($errorReport['timestamp'] ?? 'unknown');
        $output[] = "";

        // Template-specific information
        if ($errorReport['error_type'] === 'template_not_found') {
            $output[] = "Template Type: " . ($errorReport['template_type'] ?? 'unknown');
            $output[] = "Attempted Paths (" . ($errorReport['path_count'] ?? 0) . "):";
            
            foreach ($errorReport['attempted_paths'] ?? [] as $index => $path) {
                $output[] = "  " . ($index + 1) . ". " . $path;
            }
            $output[] = "";

            // Path analysis
            if (!empty($errorReport['path_analysis'])) {
                $output[] = "Path Analysis:";
                foreach ($errorReport['path_analysis'] as $analysis) {
                    $output[] = "  • " . $analysis;
                }
                $output[] = "";
            }

            // Common issues
            if (!empty($errorReport['common_issues'])) {
                $output[] = "Identified Issues:";
                foreach ($errorReport['common_issues'] as $issue) {
                    $output[] = "  📋 " . $issue['title'];
                    $output[] = "     " . $issue['description'];
                }
                $output[] = "";
            }
        }

        // Element information
        if (!empty($errorReport['element_info'])) {
            $output[] = "Element Information:";
            foreach ($errorReport['element_info'] as $key => $value) {
                $output[] = "  " . ucfirst(str_replace('_', ' ', $key)) . ": " . $value;
            }
            $output[] = "";
        }

        // Site information
        if (!empty($errorReport['site_info'])) {
            $output[] = "Site Information:";
            foreach ($errorReport['site_info'] as $key => $value) {
                $output[] = "  " . ucfirst(str_replace('_', ' ', $key)) . ": " . $value;
            }
            $output[] = "";
        }

        // Suggestions
        if (!empty($errorReport['suggestions'])) {
            $output[] = "💡 Suggestions:";
            foreach ($errorReport['suggestions'] as $index => $suggestion) {
                $output[] = "  " . ($index + 1) . ". " . $suggestion;
            }
            $output[] = "";
        }

        // Debug information
        if (!empty($errorReport['debug_info']) && Craft::$app->getConfig()->general->devMode) {
            $output[] = "🔍 Debug Information:";
            foreach ($errorReport['debug_info'] as $key => $value) {
                $output[] = "  " . ucfirst(str_replace('_', ' ', $key)) . ": " . 
                           (is_array($value) ? json_encode($value) : (string)$value);
            }
        }

        return implode("\n", $output);
    }

    /**
     * Logs an error report with appropriate severity level.
     *
     * @param array<string, mixed> $errorReport The error report to log
     * @param string $severity Log severity level (error, warning, info)
     * @return void
     */
    public function logErrorReport(array $errorReport, string $severity = 'error'): void
    {
        $message = $errorReport['primary_message'] ?? 'Unknown error';
        $context = [
            'category' => 'bonsai-twig',
            'error_report' => $errorReport,
        ];

        switch ($severity) {
            case 'warning':
                Craft::warning($message, __METHOD__, $context);
                break;
            case 'info':
                Craft::info($message, __METHOD__, $context);
                break;
            case 'error':
            default:
                Craft::error($message, __METHOD__, $context);
                break;
        }
    }

    /**
     * Extracts context information from a template context object.
     *
     * @param TemplateContext|null $context Template context
     * @return array<string, mixed> Context information
     */
    private function extractContextInfo(?TemplateContext $context): array
    {
        if (!$context) {
            return [];
        }

        return [
            'path' => $context->path,
            'style' => $context->style,
            'base_site' => $context->baseSite,
            'show_debug' => $context->showDebug,
            'variable_count' => count($context->variables),
            'has_element' => $context->element !== null,
            'has_context_element' => $context->context !== null,
        ];
    }

    /**
     * Extracts relevant information from an element for error reporting.
     *
     * @param Element $element The element to extract information from
     * @return array<string, mixed> Element information
     */
    private function extractElementInfo(Element $element): array
    {
        $info = [
            'element_type' => get_class($element),
            'element_id' => $element->id,
            'element_uid' => $element->uid,
            'site_id' => $element->siteId,
            'enabled' => $element->enabled ?? null,
            'status' => $element->status ?? null,
        ];

        // Add type-specific information
        if (method_exists($element, 'getSection')) {
            $section = $element->getSection();
            $info['section_handle'] = $section?->handle;
            $info['section_name'] = $section?->name;
        }

        if (method_exists($element, 'getType')) {
            $type = $element->getType();
            $info['type_handle'] = $type?->handle;
            $info['type_name'] = $type?->name;
        }

        if (method_exists($element, 'getGroup')) {
            $group = $element->getGroup();
            $info['group_handle'] = $group?->handle;
            $info['group_name'] = $group?->name;
        }

        return array_filter($info, fn($value) => $value !== null);
    }

    /**
     * Extracts site information for multi-site setups.
     *
     * @param TemplateContext|null $context Template context
     * @return array<string, mixed> Site information
     */
    private function extractSiteInfo(?TemplateContext $context): array
    {
        $sitesService = Craft::$app->getSites();
        $currentSite = $sitesService->getCurrentSite();
        
        $info = [
            'current_site_handle' => $currentSite->handle,
            'current_site_name' => $currentSite->name,
            'is_primary_site' => $currentSite->primary,
            'total_sites' => count($sitesService->getAllSites()),
        ];

        if ($context && $context->element) {
            $elementSite = $sitesService->getSiteById($context->element->siteId);
            if ($elementSite) {
                $info['element_site_handle'] = $elementSite->handle;
                $info['element_site_name'] = $elementSite->name;
                $info['element_site_matches_current'] = $elementSite->id === $currentSite->id;
            }
        }

        if ($context && $context->baseSite) {
            $baseSite = $sitesService->getSiteByHandle($context->baseSite);
            if ($baseSite) {
                $info['base_site_handle'] = $baseSite->handle;
                $info['base_site_name'] = $baseSite->name;
                $info['base_site_exists'] = true;
            } else {
                $info['base_site_handle'] = $context->baseSite;
                $info['base_site_exists'] = false;
            }
        }

        return $info;
    }

    /**
     * Analyzes attempted template paths to provide insights.
     *
     * @param array<string> $attemptedPaths Paths that were attempted
     * @param TemplateType $templateType Type of template being resolved
     * @return array<string> Analysis insights
     */
    private function analyzeAttemptedPaths(array $attemptedPaths, TemplateType $templateType): array
    {
        $analysis = [];
        
        if (empty($attemptedPaths)) {
            $analysis[] = "No template paths were generated for resolution";
            return $analysis;
        }

        // Check for site-specific paths
        $siteSpecificPaths = array_filter($attemptedPaths, fn($path) => 
            str_contains($path, '/_') || preg_match('/\/[a-z]{2,}\//', $path)
        );
        
        if (!empty($siteSpecificPaths)) {
            $analysis[] = sprintf(
                "Found %d site-specific template paths out of %d total paths",
                count($siteSpecificPaths),
                count($attemptedPaths)
            );
        }

        // Check for hierarchical structure
        $directories = array_unique(array_map(fn($path) => dirname($path), $attemptedPaths));
        if (count($directories) > 1) {
            $analysis[] = sprintf(
                "Templates span %d directories: %s",
                count($directories),
                implode(', ', array_slice($directories, 0, 3)) . (count($directories) > 3 ? '...' : '')
            );
        }

        // Check for template type consistency
        $typeSpecificPaths = array_filter($attemptedPaths, fn($path) => 
            str_contains($path, $templateType->value)
        );
        
        if (count($typeSpecificPaths) !== count($attemptedPaths)) {
            $analysis[] = sprintf(
                "Only %d out of %d paths are %s-specific",
                count($typeSpecificPaths),
                count($attemptedPaths),
                $templateType->value
            );
        }

        return $analysis;
    }

    /**
     * Identifies common issues based on attempted template paths.
     *
     * @param array<string> $attemptedPaths Paths that were attempted
     * @return array<array<string, string>> Identified common issues
     */
    private function identifyCommonIssues(array $attemptedPaths): array
    {
        $identifiedIssues = [];
        
        foreach (self::COMMON_ISSUES as $issueKey => $issueData) {
            $matchingPaths = array_filter($attemptedPaths, fn($path) => 
                preg_match($issueData['pattern'], $path)
            );
            
            if (!empty($matchingPaths)) {
                $identifiedIssues[] = [
                    'key' => $issueKey,
                    'title' => $issueData['title'],
                    'description' => $issueData['description'],
                    'matching_paths' => array_values($matchingPaths),
                    'suggestions' => $issueData['suggestions'],
                ];
            }
        }
        
        return $identifiedIssues;
    }

    /**
     * Generates helpful suggestions based on the error context.
     *
     * @param array<string> $attemptedPaths Paths that were attempted
     * @param TemplateType $templateType Type of template being resolved
     * @param TemplateContext|null $context Template context
     * @return array<string> Helpful suggestions
     */
    private function generateSuggestions(
        array $attemptedPaths,
        TemplateType $templateType,
        ?TemplateContext $context
    ): array {
        $suggestions = [];
        
        // General suggestions
        $suggestions[] = "Check that template files exist in the Craft templates directory";
        $suggestions[] = "Verify file permissions allow reading template files";
        $suggestions[] = "Ensure template file extensions are .twig or .html";
        
        // Type-specific suggestions
        switch ($templateType) {
            case TemplateType::ENTRY:
                $suggestions[] = "Create entry templates in templates/entry/ directory";
                $suggestions[] = "Use section handle for template filename (e.g., news.twig)";
                $suggestions[] = "Create entry type templates in templates/entry/sectionHandle/ directory";
                break;
                
            case TemplateType::CATEGORY:
                $suggestions[] = "Create category templates in templates/category/ directory";
                $suggestions[] = "Use category group handle for template filename";
                break;
                
            case TemplateType::MATRIX:
                $suggestions[] = "Create matrix block templates in templates/matrix/ directory";
                $suggestions[] = "Use matrix block type handle for template filename";
                $suggestions[] = "Consider creating style-specific templates for matrix blocks";
                break;
        }
        
        // Multi-site suggestions
        if (Craft::$app->getIsMultiSite()) {
            $suggestions[] = "For site-specific templates, create _siteHandle subdirectories";
            $suggestions[] = "Ensure site handles match directory names exactly";
        }
        
        // Context-specific suggestions
        if ($context && $context->element) {
            if (method_exists($context->element, 'getSection')) {
                $section = $context->element->getSection();
                if ($section) {
                    $suggestions[] = "Create template: templates/entry/{$section->handle}.twig";
                }
            }
            
            if (method_exists($context->element, 'getGroup')) {
                $group = $context->element->getGroup();
                if ($group) {
                    $suggestions[] = "Create template: templates/category/{$group->handle}.twig";
                }
            }
        }
        
        return array_unique($suggestions);
    }

    /**
     * Generates suggestions for element validation errors.
     *
     * @param string $expectedType Expected element type
     * @param mixed $providedValue Value that was provided
     * @return array<string> Validation suggestions
     */
    private function generateElementValidationSuggestions(string $expectedType, mixed $providedValue): array
    {
        $suggestions = [];
        
        $suggestions[] = "Ensure you're passing a valid Craft element to the template function";
        $suggestions[] = "Check that the element variable is properly set in your template";
        
        if ($providedValue === null) {
            $suggestions[] = "The element appears to be null - check your element query";
            $suggestions[] = "Verify the element exists and is enabled";
        } elseif (is_array($providedValue)) {
            $suggestions[] = "You passed an array instead of an element - use .one() or .first() on your query";
        } elseif (is_string($providedValue)) {
            $suggestions[] = "You passed a string instead of an element - ensure you're not passing an ID or handle";
        }
        
        $suggestions[] = "Expected type: {$expectedType}";
        $suggestions[] = "Actual type: " . get_debug_type($providedValue);
        
        return $suggestions;
    }

    /**
     * Sanitizes a value for safe logging.
     *
     * @param mixed $value Value to sanitize
     * @return string Sanitized value representation
     */
    private function sanitizeValueForLogging(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_scalar($value)) {
            $stringValue = (string)$value;
            return strlen($stringValue) > 100 ? substr($stringValue, 0, 100) . '...' : $stringValue;
        }
        
        if (is_array($value)) {
            return 'Array(' . count($value) . ' items)';
        }
        
        if (is_object($value)) {
            return get_class($value) . ' object';
        }
        
        return get_debug_type($value);
    }
}