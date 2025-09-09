<?php

namespace wabisoft\bonsaitwig\exceptions;

/**
 * Exception thrown when an invalid or potentially dangerous template path is detected.
 * 
 * This exception is used for security purposes to prevent path traversal attacks
 * and other path-related security issues.
 */
class InvalidTemplatePathException extends BonsaiTwigException
{
    /**
     * @param string $path The invalid path that was provided
     * @param string $reason The reason why the path is invalid
     * @param string $message Optional custom error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception for chaining
     */
    public function __construct(
        public readonly string $path,
        public readonly string $reason,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if (empty($message)) {
            $message = sprintf(
                'Invalid template path "%s": %s',
                $path,
                $reason
            );
        }
        
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the invalid path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the reason why the path is invalid.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Get enhanced context for logging purposes.
     * 
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return array_merge(parent::getContext(), [
            'invalidPath' => $this->path,
            'reason' => $this->reason,
        ]);
    }
}