<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class InvalidNewPassword extends DomainError
{
    public function __construct(
        private readonly int $minLength,
        private readonly int $maxLength
    )
    {
        parent::__construct(
            sprintf('The password must be between %d and %d characters long', $minLength, $maxLength)
        );
    }

    public function errorCode(): string
    {
        return 'user.invalid_new_password';
    }

    /**
     * @return array<string, mixed>
     */
    public function params(): array
    {
        return [
            'min' => $this->minLength,
            'max' => $this->maxLength,
        ];
    }
}
