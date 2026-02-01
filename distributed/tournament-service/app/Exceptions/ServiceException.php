<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for service-related errors
 */
class ServiceException extends Exception
{
    protected ?string $serviceName;
    protected ?string $errorCode;
    protected array $context;

    public function __construct(
        string $message = 'Service error',
        int $code = 500,
        ?string $serviceName = null,
        ?string $errorCode = null,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->serviceName = $serviceName;
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    public function getServiceName(): ?string
    {
        return $this->serviceName;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'service' => $this->serviceName,
            'error_code' => $this->errorCode,
            'context' => $this->context,
        ];
    }
}
