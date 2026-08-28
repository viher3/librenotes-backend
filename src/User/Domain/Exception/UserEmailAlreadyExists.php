<?php

namespace App\User\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

final class UserEmailAlreadyExists extends DomainError
{
    public function __construct(private string $value)
    {
        $message = 'Email already exists: ' . $this->value;
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'user.email_already_exists';
    }

    public function params(): array
    {
        return [
            'value' => $this->value
        ];
    }
}
