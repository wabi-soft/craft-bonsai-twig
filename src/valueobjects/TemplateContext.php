<?php

namespace wabisoft\bonsaitwig\valueobjects;

use craft\base\Element;

/**
 * Value object representing the context for template resolution.
 *
 * This readonly class encapsulates all the parameters needed for template loading
 * and provides validation methods to ensure data integrity. It leverages PHP 8.2
 * readonly properties and named parameters for better developer experience.
 *
 * @since 6.4.0
 */
readonly class TemplateContext
{
    /**
     * Creates a new template context with the specified parameters.
     *
     * @param Element $element The Craft element to load templates for
     * @param string $path The base template path (defaults to 'entry')
     * @param string|null $style Optional style parameter for template variants
     * @param Element|null $context Optional context element for hierarchical resolution
     * @param string|null $baseSite Optional base site handle for multi-site resolution
     * @param array<string, mixed> $variables Additional template variables
     * @param bool $showDebug Whether to show debug information
     */
    public function __construct(
        public Element $element,
        public string $path = 'entry',
        public ?string $style = null,
        public ?Element $context = null,
        public ?string $baseSite = null,
        public array $variables = [],
        public bool $showDebug = false,
    ) {
        $this->validateElement($element);
        $this->validatePath($path);
        $this->validateStyle($style);
        $this->validateBaseSite($baseSite);
        $this->validateVariables($variables);
    }

    /**
     * Validates that the element is a valid Craft element.
     *
     * @param Element $element The element to validate
     * @throws \InvalidArgumentException If the element is invalid
     */
    private function validateElement(Element $element): void
    {
        // Type is enforced by PHP type hint, no need for instanceof check

        if ($element->id === null) {
            throw new \InvalidArgumentException('Element must have a valid ID');
        }

        // Validate element state for template resolution
        if ($element->siteId === null) {
            throw new \InvalidArgumentException('Element must have a valid site ID');
        }
    }

    /**
     * Validates the template path parameter.
     *
     * @param string $path The path to validate
     * @throws \InvalidArgumentException If the path is invalid
     */
    private function validatePath(string $path): void
    {
        if (empty(trim($path))) {
            throw new \InvalidArgumentException('Template path cannot be empty');
        }

        // Prevent path traversal attacks
        if (str_contains($path, '..') || str_contains($path, '\\')) {
            throw new \InvalidArgumentException('Template path contains invalid characters');
        }

        // Ensure path doesn't start with slash
        if (str_starts_with($path, '/')) {
            throw new \InvalidArgumentException('Template path should not start with a slash');
        }
    }

    /**
     * Validates the style parameter if provided.
     *
     * @param string|null $style The style to validate
     * @throws \InvalidArgumentException If the style is invalid
     */
    private function validateStyle(?string $style): void
    {
        if ($style === null) {
            return;
        }

        if (empty(trim($style))) {
            throw new \InvalidArgumentException('Style parameter cannot be empty string');
        }

        // Prevent path traversal in style parameter
        if (str_contains($style, '..') || str_contains($style, '/') || str_contains($style, '\\')) {
            throw new \InvalidArgumentException('Style parameter contains invalid characters');
        }
    }

    /**
     * Validates the base site parameter if provided.
     *
     * @param string|null $baseSite The base site to validate
     * @throws \InvalidArgumentException If the base site is invalid
     */
    private function validateBaseSite(?string $baseSite): void
    {
        if ($baseSite === null) {
            return;
        }

        if (empty(trim($baseSite))) {
            throw new \InvalidArgumentException('Base site parameter cannot be empty string');
        }

        // Basic validation for site handle format
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $baseSite)) {
            throw new \InvalidArgumentException('Base site handle contains invalid characters');
        }
    }

    /**
     * Validates the variables array.
     *
     * @param array<string, mixed> $variables The variables to validate
     * @throws \InvalidArgumentException If the variables array is invalid
     */
    private function validateVariables(array $variables): void
    {
        foreach (array_keys($variables) as $key) {
            if (!is_string($key) || empty(trim($key))) {
                throw new \InvalidArgumentException('Variable keys must be non-empty strings');
            }
        }
    }

    /**
     * Returns the sanitized template path.
     *
     * @return string The sanitized path
     */
    public function getSanitizedPath(): string
    {
        return trim($this->path, '/ ');
    }

    /**
     * Returns the element type as a string.
     *
     * @return string The element type
     */
    public function getElementType(): string
    {
        return $this->element::class;
    }

    /**
     * Checks if debug mode is enabled.
     *
     * @return bool True if debug mode is enabled
     */
    public function isDebugEnabled(): bool
    {
        return $this->showDebug;
    }

    /**
     * Returns a new instance with updated debug setting.
     *
     * @param bool $showDebug Whether to show debug information
     * @return self New instance with updated debug setting
     */
    public function withDebug(bool $showDebug): self
    {
        return new self(
            element: $this->element,
            path: $this->path,
            style: $this->style,
            context: $this->context,
            baseSite: $this->baseSite,
            variables: $this->variables,
            showDebug: $showDebug
        );
    }

    /**
     * Returns a new instance with additional variables.
     *
     * @param array<string, mixed> $additionalVariables Variables to merge
     * @return self New instance with merged variables
     */
    public function withVariables(array $additionalVariables): self
    {
        $this->validateVariables($additionalVariables);
        
        return new self(
            element: $this->element,
            path: $this->path,
            style: $this->style,
            context: $this->context,
            baseSite: $this->baseSite,
            variables: array_merge($this->variables, $additionalVariables),
            showDebug: $this->showDebug
        );
    }

    /**
     * Returns a new instance with updated style.
     *
     * @param string|null $style The new style parameter
     * @return self New instance with updated style
     */
    public function withStyle(?string $style): self
    {
        $this->validateStyle($style);
        
        return new self(
            element: $this->element,
            path: $this->path,
            style: $style,
            context: $this->context,
            baseSite: $this->baseSite,
            variables: $this->variables,
            showDebug: $this->showDebug
        );
    }
}
