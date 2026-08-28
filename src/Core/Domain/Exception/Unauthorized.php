<?php

namespace App\Core\Domain\Exception;

class Unauthorized extends DomainError
{
    public function __construct(
        string $message = 'Not logged user'
    )
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'authorization.unauthorized';
    }

    public function params(): array
    {
        return [];
    }
}