<?php

namespace wabisoft\bonsaitwig\valueobjects;

use wabisoft\bonsaitwig\enums\TemplateType;

/**
 * Value object containing debug information for template resolution.
 *
 * This readonly class encapsulates debugging data including timing information,
 * template paths, and performance metrics. It provides JSON serialization
 * capabilities for easy output in debug mode.
 *
 * @since 6.4.0
 */
readonly class DebugInfo implements \JsonSerializable
{
    /**
     * Creates a new debug info object with the specified parameters.
     *
     * @param string $directory The base directory being searched
     * @param array<string> $templates Array of template paths that were checked
     * @param string $currentTemplate The template that was ultimately selected
     * @param TemplateType $type The type of template being resolved
     * @param float $resolutionTime Time taken to resolve the template in seconds
     * @param float $memoryUsage Memory usage during resolution in bytes
     * @param array<string, mixed> $elementData Relevant element data for debugging
     * @param array<string, mixed> $contextData Additional context information
     */
    public function __construct(
        public string $directory,
        public array $templates,
        public string $currentTemplate,
        public TemplateType $type,
        public float $resolutionTime = 0.0,
        public float $memoryUsage = 0.0,
        public array $elementData = [],
        public array $contextData = []
    ) {
        $this->validateDirectory($directory);
        $this->validateTemplates($templates);
        $this->validateCurrentTemplate($currentTemplate);
        $this->validateTiming($resolutionTime);
        $this->validateMemoryUsage($memoryUsage);
    }

    /**
     * Validates the directory parameter.
     *
     * @param string $directory The directory to validate
     * @throws \InvalidArgumentException If the directory is invalid
     */
    private function validateDirectory(string $directory): void
    {
        if (empty(trim($directory))) {
            throw new \InvalidArgumentException('Directory cannot be empty');
        }
    }

    /**
     * Validates the templates array.
     *
     * @param array<string> $templates The templates array to validate
     * @throws \InvalidArgumentException If the templates array is invalid
     */
    private function validateTemplates(array $templates): void
    {
        foreach ($templates as $template) {
            if (!is_string($template)) {
                throw new \InvalidArgumentException('All template entries must be strings');
            }
        }
    }

    /**
     * Validates the current template parameter.
     *
     * @param string $currentTemplate The current template to validate
     * @throws \InvalidArgumentException If the current template is invalid
     */
    private function validateCurrentTemplate(string $currentTemplate): void
    {
        if (empty(trim($currentTemplate))) {
            throw new \InvalidArgumentException('Current template cannot be empty');
        }
    }

    /**
     * Validates the resolution time parameter.
     *
     * @param float $resolutionTime The resolution time to validate
     * @throws \InvalidArgumentException If the resolution time is invalid
     */
    private function validateTiming(float $resolutionTime): void
    {
        if ($resolutionTime < 0) {
            throw new \InvalidArgumentException('Resolution time cannot be negative');
        }
    }

    /**
     * Validates the memory usage parameter.
     *
     * @param float $memoryUsage The memory usage to validate
     * @throws \InvalidArgumentException If the memory usage is invalid
     */
    private function validateMemoryUsage(float $memoryUsage): void
    {
        if ($memoryUsage < 0) {
            throw new \InvalidArgumentException('Memory usage cannot be negative');
        }
    }

    /**
     * Returns the resolution time formatted for display.
     *
     * @return string Formatted resolution time
     */
    public function getFormattedResolutionTime(): string
    {
        if ($this->resolutionTime < 0.001) {
            return sprintf('%.3f μs', $this->resolutionTime * 1_000_000);
        }
        
        if ($this->resolutionTime < 1.0) {
            return sprintf('%.2f ms', $this->resolutionTime * 1000);
        }
        
        return sprintf('%.3f s', $this->resolutionTime);
    }

    /**
     * Returns the memory usage formatted for display.
     *
     * @return string Formatted memory usage
     */
    public function getFormattedMemoryUsage(): string
    {
        if ($this->memoryUsage < 1024) {
            return sprintf('%.0f B', $this->memoryUsage);
        }
        
        if ($this->memoryUsage < 1024 * 1024) {
            return sprintf('%.2f KB', $this->memoryUsage / 1024);
        }
        
        if ($this->memoryUsage < 1024 * 1024 * 1024) {
            return sprintf('%.2f MB', $this->memoryUsage / (1024 * 1024));
        }
        
        return sprintf('%.2f GB', $this->memoryUsage / (1024 * 1024 * 1024));
    }

    /**
     * Returns the number of templates that were checked.
     *
     * @return int Number of templates checked
     */
    public function getTemplateCount(): int
    {
        return count($this->templates);
    }

    /**
     * Checks if any templates were found during resolution.
     *
     * @return bool True if templates were found
     */
    public function hasTemplates(): bool
    {
        return !empty($this->templates);
    }

    /**
     * Returns templates that were checked but not found.
     *
     * @return array<string> Array of templates that were not found
     */
    public function getMissingTemplates(): array
    {
        return array_filter($this->templates, fn(string $template): bool => 
            $template !== $this->currentTemplate
        );
    }

    /**
     * Checks if the resolution was considered slow.
     *
     * @param float $threshold Threshold in seconds (default: 0.1)
     * @return bool True if resolution was slow
     */
    public function isSlowResolution(float $threshold = 0.1): bool
    {
        return $this->resolutionTime > $threshold;
    }

    /**
     * Checks if memory usage was high during resolution.
     *
     * @param float $threshold Threshold in bytes (default: 1MB)
     * @return bool True if memory usage was high
     */
    public function isHighMemoryUsage(float $threshold = 1024 * 1024): bool
    {
        return $this->memoryUsage > $threshold;
    }

    /**
     * Returns performance summary information.
     *
     * @return array<string, mixed> Performance summary
     */
    public function getPerformanceSummary(): array
    {
        return [
            'resolutionTime' => $this->getFormattedResolutionTime(),
            'memoryUsage' => $this->getFormattedMemoryUsage(),
            'templateCount' => $this->getTemplateCount(),
            'isSlowResolution' => $this->isSlowResolution(),
            'isHighMemoryUsage' => $this->isHighMemoryUsage(),
        ];
    }

    /**
     * Returns a summary of the template resolution process.
     *
     * @return array<string, mixed> Resolution summary
     */
    public function getResolutionSummary(): array
    {
        return [
            'type' => $this->type->value,
            'directory' => $this->directory,
            'selectedTemplate' => $this->currentTemplate,
            'templatesChecked' => $this->getTemplateCount(),
            'missingTemplates' => $this->getMissingTemplates(),
        ];
    }

    /**
     * Specifies data which should be serialized to JSON.
     *
     * @return array<string, mixed> Data to be serialized
     */
    public function jsonSerialize(): array
    {
        return [
            'directory' => $this->directory,
            'templates' => $this->templates,
            'currentTemplate' => $this->currentTemplate,
            'type' => $this->type->value,
            'performance' => $this->getPerformanceSummary(),
            'resolution' => $this->getResolutionSummary(),
            'elementData' => $this->elementData,
            'contextData' => $this->contextData,
            'timestamp' => date('c'),
        ];
    }

    /**
     * Returns a new instance with additional element data.
     *
     * @param array<string, mixed> $elementData Element data to add
     * @return self New instance with additional element data
     */
    public function withElementData(array $elementData): self
    {
        return new self(
            directory: $this->directory,
            templates: $this->templates,
            currentTemplate: $this->currentTemplate,
            type: $this->type,
            resolutionTime: $this->resolutionTime,
            memoryUsage: $this->memoryUsage,
            elementData: array_merge($this->elementData, $elementData),
            contextData: $this->contextData
        );
    }

    /**
     * Returns a new instance with additional context data.
     *
     * @param array<string, mixed> $contextData Context data to add
     * @return self New instance with additional context data
     */
    public function withContextData(array $contextData): self
    {
        return new self(
            directory: $this->directory,
            templates: $this->templates,
            currentTemplate: $this->currentTemplate,
            type: $this->type,
            resolutionTime: $this->resolutionTime,
            memoryUsage: $this->memoryUsage,
            elementData: $this->elementData,
            contextData: array_merge($this->contextData, $contextData)
        );
    }

    /**
     * Creates a debug info instance from timing measurements.
     *
     * @param string $directory The directory being searched
     * @param array<string> $templates Templates that were checked
     * @param string $currentTemplate The selected template
     * @param TemplateType $type The template type
     * @param float $startTime Start time from microtime(true)
     * @param float $startMemory Start memory from memory_get_usage()
     * @return self New debug info instance
     */
    public static function fromTiming(
        string $directory,
        array $templates,
        string $currentTemplate,
        TemplateType $type,
        float $startTime,
        float $startMemory
    ): self {
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        return new self(
            directory: $directory,
            templates: $templates,
            currentTemplate: $currentTemplate,
            type: $type,
            resolutionTime: $endTime - $startTime,
            memoryUsage: $endMemory - $startMemory
        );
    }
}