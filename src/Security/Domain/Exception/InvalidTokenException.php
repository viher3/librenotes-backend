<?php

declare(strict_types=1);

namespace App\Security\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class InvalidTokenException extends DomainError
{
    private const string DEFAULT_MESSAGE = 'Invalid token';

    public function __construct(
        string $error = '',
        ?\Throwable $exception = null
    )
    {
        $error = !empty($error) ? $error : $this->errorMessage();
        parent::__construct($error, $exception);
    }

    public function errorCode(): string
    {
        return 'security.invalid_token';
    }

    public function params(): array
    {
        return [];
    }

    protected function errorMessage(): string
    {
        return self::DEFAULT_MESSAGE;
    }
}
