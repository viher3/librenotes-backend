<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

final class UserRegistrationDisabled extends DomainError
{
    public function __construct()
    {
        parent::__construct('User registration is disabled');
    }

    public function errorCode(): string
    {
        return 'user.registration_disabled';
    }

    public function params(): array
    {
        return [];
    }
}
