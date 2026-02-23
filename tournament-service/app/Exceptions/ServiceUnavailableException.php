<?php

namespace App\Exceptions;

use Throwable;

/**
 * Exception thrown when a service is unavailable
 */
class ServiceUnavailableException extends ServiceException
{
    public function __construct(
        string $message = 'Service unavailable',
        ?string $serviceName = null,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            503,
            $serviceName,
            'SERVICE_UNAVAILABLE',
            $context,
            $previous
        );
    }
}
