<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class ChangePasswordRateLimited extends DomainError
{
    public function __construct(
        private readonly int $retryAfter
    )
    {
        parent::__construct('Too many failed attempts to change the password');
    }

    public function errorCode(): string
    {
        return 'user.change_password_rate_limited';
    }

    /**
     * @return array{retry_after: int}
     */
    public function params(): array
    {
        return ['retry_after' => $this->retryAfter];
    }

    public function retryAfter(): int
    {
        return $this->retryAfter;
    }
}
