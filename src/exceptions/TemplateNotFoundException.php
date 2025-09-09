<?php

namespace wabisoft\bonsaitwig\exceptions;

use wabisoft\bonsaitwig\enums\TemplateType;

/**
 * Exception thrown when a template cannot be found during template resolution.
 * 
 * This exception provides detailed context about the template resolution attempt,
 * including all attempted paths and the template type being resolved.
 */
class TemplateNotFoundException extends BonsaiTwigException
{
    /**
     * @param array<string> $attemptedPaths Array of template paths that were attempted
     * @param TemplateType $templateType The type of template being resolved
     * @param string $message Optional custom error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception for chaining
     */
    public function __construct(
        public readonly array $attemptedPaths,
        public readonly TemplateType $templateType,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if (empty($message)) {
            $message = sprintf(
                'No template found for %s. Attempted paths: %s',
                $templateType->value,
                implode(', ', $attemptedPaths)
            );
        }
        
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the attempted template paths.
     * 
     * @return array<string>
     */
    public function getAttemptedPaths(): array
    {
        return $this->attemptedPaths;
    }

    /**
     * Get the template type that was being resolved.
     */
    public function getTemplateType(): TemplateType
    {
        return $this->templateType;
    }

    /**
     * Get a formatted string of attempted paths for logging.
     */
    public function getFormattedAttemptedPaths(): string
    {
        return implode("\n- ", $this->attemptedPaths);
    }

    /**
     * Get enhanced context for logging purposes.
     * 
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return array_merge(parent::getContext(), [
            'templateType' => $this->templateType->value,
            'attemptedPaths' => $this->attemptedPaths,
            'pathCount' => count($this->attemptedPaths),
        ]);
    }
}