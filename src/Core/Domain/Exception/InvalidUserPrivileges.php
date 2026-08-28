<?php

namespace App\Core\Domain\Exception;

class InvalidUserPrivileges extends DomainError
{
    public function __construct(
        string $message = 'Invalid user privileges'
    )
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'user.invalid_privileges';
    }

    public function params(): array
    {
        return [];
    }
}