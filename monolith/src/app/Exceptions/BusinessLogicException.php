<?php

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for business logic errors
 * These are user-facing errors that should be shown to users
 */
class BusinessLogicException extends Exception
{
    protected $userMessage;
    protected $context;

    public function __construct(string $message, string $userMessage = null, array $context = [], int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->userMessage = $userMessage ?? $message;
        $this->context = $context;
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
