<?php

namespace App\User\Domain\Exception;

use App\Core\Domain\Exception\CriticalException;

class ErrorUserChangePassword extends CriticalException
{
    private const string DEFAULT_MESSAGE = 'An error occurred while changing the user password';

    public static function fromPrevious(\Throwable $exception) : self
    {
        return new self(self::DEFAULT_MESSAGE . ': '. $exception->getMessage(), 0, $exception);
    }
}
