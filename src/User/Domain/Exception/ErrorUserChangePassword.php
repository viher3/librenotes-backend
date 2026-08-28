<?php

namespace App\User\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class ErrorUserChangePassword extends DomainError
{
    private const string DEFAULT_MESSAGE = 'An error occurred while changing the user password';

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
        return 'user.change_password_error';
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
