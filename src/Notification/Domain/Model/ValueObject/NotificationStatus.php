<?php

declare(strict_types=1);

namespace App\Notification\Domain\Model\ValueObject;

use App\Core\Domain\ValueObject\StringValueObject;
use App\Notification\Domain\Exception\InvalidNotificationStatusException;

final class NotificationStatus extends StringValueObject
{
    public const PENDING = 'pending';
    public const SENT = 'sent';
    public const FAILED = 'failed';

    private const VALID_STATUSES = [
        self::PENDING,
        self::SENT,
        self::FAILED,
    ];

    public function __construct(string $value)
    {
        if (!in_array($value, self::VALID_STATUSES, true)) {
            throw new InvalidNotificationStatusException($value);
        }

        parent::__construct($value);
    }

    public function equals(NotificationStatus $other): bool
    {
        return $this->value() === $other->value();
    }
}
