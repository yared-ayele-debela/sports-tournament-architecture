<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception for when a resource is not found
 */
class ResourceNotFoundException extends Exception
{
    protected $resourceType;
    protected $resourceId;

    public function __construct(string $resourceType, $resourceId = null, string $message = null, int $code = 404, \Throwable $previous = null)
    {
        $message = $message ?? "{$resourceType} not found" . ($resourceId ? " (ID: {$resourceId})" : '');
        parent::__construct($message, $code, $previous);
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function getResourceId()
    {
        return $this->resourceId;
    }
}
