<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exception;

use App\Core\Domain\Exception\InvalidValueException;

final class InvalidNotificationStatusException extends InvalidValueException
{
    public function errorCode(): string
    {
        return 'notification.invalid_status';
    }
}
