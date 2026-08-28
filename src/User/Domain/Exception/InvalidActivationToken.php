<?php

namespace App\User\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

final class InvalidActivationToken extends DomainError
{
    public function __construct()
    {
        parent::__construct('Invalid or already used activation token');
    }

    public function errorCode(): string
    {
        return 'user.invalid_activation_token';
    }

    /**
     * @return array<string, string>
     */
    public function params(): array
    {
        return [];
    }
}
