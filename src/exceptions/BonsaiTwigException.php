<?php

namespace wabisoft\bonsaitwig\exceptions;

/**
 * Base exception class for all Bonsai Twig plugin exceptions.
 * 
 * This provides a common base for all plugin-specific exceptions,
 * making it easier to catch and handle plugin-related errors.
 */
abstract class BonsaiTwigException extends \Exception
{
    /**
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous Previous exception for chaining
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get a context array for logging purposes.
     * 
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return [
            'exception' => static::class,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}