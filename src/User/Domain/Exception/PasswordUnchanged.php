<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class PasswordUnchanged extends DomainError
{
    public function __construct()
    {
        parent::__construct('The new password must be different from the current one');
    }

    public function errorCode(): string
    {
        return 'user.password_unchanged';
    }

    /**
     * @return array<string, mixed>
     */
    public function params(): array
    {
        return [];
    }
}
