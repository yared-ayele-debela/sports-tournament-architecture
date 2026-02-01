<?php

namespace App\Exceptions;

use Throwable;

/**
 * Exception thrown when a service request fails
 */
class ServiceRequestException extends ServiceException
{
    protected ?int $httpStatusCode;

    public function __construct(
        string $message = 'Service request failed',
        ?string $serviceName = null,
        ?int $httpStatusCode = null,
        array $context = [],
        ?Throwable $previous = null
    ) {
        $code = $httpStatusCode ?? 500;
        parent::__construct(
            $message,
            $code,
            $serviceName,
            'SERVICE_REQUEST_FAILED',
            $context,
            $previous
        );
        $this->httpStatusCode = $httpStatusCode;
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }
}
