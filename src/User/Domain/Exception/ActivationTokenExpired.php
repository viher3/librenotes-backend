<?php

namespace App\User\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

final class ActivationTokenExpired extends DomainError
{
    public function __construct()
    {
        parent::__construct('Activation token has expired');
    }

    public function errorCode(): string
    {
        return 'user.activation_token_expired';
    }

    /**
     * @return array<string, string>
     */
    public function params(): array
    {
        return [];
    }
}
