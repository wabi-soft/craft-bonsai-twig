<?php

namespace wabisoft\bonsaitwig\exceptions;

use Exception;

/**
 * BonsaiTwigException
 *
 * Base exception class for all Bonsai Twig plugin exceptions.
 * Provides a common exception type for plugin-specific errors.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\exceptions
 * @since 6.4.0
 */
class BonsaiTwigException extends Exception
{
    /**
     * Constructor for BonsaiTwigException.
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param Exception|null $previous The previous exception for chaining
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}