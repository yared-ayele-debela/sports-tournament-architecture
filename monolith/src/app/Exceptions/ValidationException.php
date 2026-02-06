<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\MessageBag;

/**
 * Custom validation exception for business rule violations
 */
class ValidationException extends Exception
{
    protected $errors;

    public function __construct(string $message, MessageBag $errors = null, int $code = 422, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors ?? new MessageBag();
    }

    public function getErrors(): MessageBag
    {
        return $this->errors;
    }
}
