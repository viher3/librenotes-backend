<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Exception;

use App\Core\Domain\Exception\InvalidValueException;

final class InvalidConfigurationTypeException extends InvalidValueException
{
    public function errorCode(): string
    {
        return 'configuration.invalid_type';
    }
}
