<?php

declare(strict_types=1);

namespace Trees\Exception;

use Exception;
use Throwable;

class TreesException extends Exception
{
    private array $errors;

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, array $errors = [])
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}