<?php

declare(strict_types=1);

namespace App\Security\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class BadCredentialsException extends DomainError
{
    private const string DEFAULT_MESSAGE = 'Bad credentials';

    public function __construct(
        string $error = '',
        ?\Throwable $exception = null
    )
    {
        $error = !empty($error) ? $error : $this->errorMessage();
        parent::__construct($error, $exception);
    }

    public static function fromPrevious(\Throwable $exception) : self
    {
        return new self(self::DEFAULT_MESSAGE . ': '. $exception->getMessage(), $exception);
    }

    public function errorCode(): string
    {
        return 'security.bad_credentials';
    }

    protected function errorMessage(): string
    {
        return self::DEFAULT_MESSAGE;
    }

    public function params(): array
    {
        return [];
    }
}
