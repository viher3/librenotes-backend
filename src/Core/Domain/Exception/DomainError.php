<?php

namespace App\Core\Domain\Exception;

use DomainException;

abstract class DomainError extends DomainException
{
    public function __construct(string $message, ?\Throwable $throwable = null)
    {
        parent::__construct($message, 0, $throwable);
    }

    abstract public function errorCode(): string;

    abstract public function params(): array;
}
