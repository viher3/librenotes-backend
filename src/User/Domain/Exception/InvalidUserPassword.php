<?php

namespace App\User\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class InvalidUserPassword extends DomainError
{
    public function __construct(string $error = '')
    {
        $error = !empty($error) ? $error : $this->errorMessage();
        parent::__construct($error);
    }

    public function errorCode(): string
    {
        return 'user.invalid_password';
    }

    protected function errorMessage(): string
    {
        return 'Invalid password';
    }

    public function params(): array
    {
        return [];
    }
}
