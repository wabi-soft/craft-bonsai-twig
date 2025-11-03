<?php

namespace wabisoft\bonsaitwig\exceptions;

/**
 * Exception thrown when a template cannot be found during template resolution.
 */
class TemplateNotFoundException extends \RuntimeException
{
    /**
     * @param array<string> $attemptedPaths Array of template paths that were attempted
     * @param string $templateType The type of template being resolved
     */
    public function __construct(array $attemptedPaths, string $templateType)
    {
        $message = sprintf(
            'No %s template found. Tried: %s',
            $templateType,
            implode(', ', $attemptedPaths)
        );
        
        parent::__construct($message);
    }
}
