<?php

namespace wabisoft\bonsaitwig\exceptions;

/**
 * Exception thrown when an invalid element is provided to template loading functions.
 *
 * This exception provides detailed information about the expected element type
 * and what was actually provided, helping developers identify type mismatches.
 */
class InvalidElementException extends BonsaiTwigException
{
    /**
     * @param string $expectedType The expected element type or class name
     * @param mixed $actualValue The actual value that was provided
     * @param string $message Optional custom error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception for chaining
     */
    public function __construct(
        public readonly string $expectedType,
        public readonly mixed $actualValue,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        if (empty($message)) {
            $actualType = get_debug_type($actualValue);
            $message = sprintf(
                'Expected %s, got %s',
                $expectedType,
                $actualType
            );
        }
        
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the expected element type.
     */
    public function getExpectedType(): string
    {
        return $this->expectedType;
    }

    /**
     * Get the actual value that was provided.
     */
    public function getActualValue(): mixed
    {
        return $this->actualValue;
    }

    /**
     * Get the actual type of the provided value.
     */
    public function getActualType(): string
    {
        return get_debug_type($this->actualValue);
    }

    /**
     * Check if the actual value is null.
     */
    public function isActualValueNull(): bool
    {
        return $this->actualValue === null;
    }

    /**
     * Get enhanced context for logging purposes.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return array_merge(parent::getContext(), [
            'expectedType' => $this->expectedType,
            'actualType' => $this->getActualType(),
            'isNull' => $this->isActualValueNull(),
        ]);
    }
}
