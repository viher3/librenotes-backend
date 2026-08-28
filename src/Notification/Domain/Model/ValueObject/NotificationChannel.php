<?php

declare(strict_types=1);

namespace App\Notification\Domain\Model\ValueObject;

use App\Core\Domain\ValueObject\StringValueObject;
use App\Notification\Domain\Exception\InvalidNotificationChannelException;

final class NotificationChannel extends StringValueObject
{
    public const EMAIL = 'email';

    private const VALID_CHANNELS = [
        self::EMAIL,
    ];

    public function __construct(string $value)
    {
        if (!in_array($value, self::VALID_CHANNELS, true)) {
            throw new InvalidNotificationChannelException($value);
        }

        parent::__construct($value);
    }

    public function equals(NotificationChannel $other): bool
    {
        return $this->value() === $other->value();
    }
}
