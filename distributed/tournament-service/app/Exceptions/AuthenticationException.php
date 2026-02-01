<?php

namespace App\Exceptions;

use Throwable;

/**
 * Exception thrown when authentication fails
 */
class AuthenticationException extends ServiceException
{
    public function __construct(
        string $message = 'Authentication failed',
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            401,
            'auth-service',
            'AUTHENTICATION_FAILED',
            $context,
            $previous
        );
    }
}
