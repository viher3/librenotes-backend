<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exception;

use App\Core\Domain\Exception\InvalidValueException;

final class InvalidNotificationTemplateKeyFormatException extends InvalidValueException
{
    public function errorCode(): string
    {
        return 'notification.invalid_template_key_format';
    }
}
