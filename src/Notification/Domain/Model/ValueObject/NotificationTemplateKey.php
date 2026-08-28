<?php

declare(strict_types=1);

namespace App\Notification\Domain\Model\ValueObject;

use App\Core\Domain\ValueObject\StringValueObject;
use App\Notification\Domain\Exception\InvalidNotificationTemplateKeyFormatException;

final class NotificationTemplateKey extends StringValueObject
{
    private const VALID_REGEX = '/^[a-z0-9]+(\.[a-z0-9_]+)*$/';

    public function __construct(string $value)
    {
        if (!preg_match(self::VALID_REGEX, $value)) {
            throw new InvalidNotificationTemplateKeyFormatException($value);
        }

        parent::__construct($value);
    }

    public function equals(NotificationTemplateKey $other): bool
    {
        return $this->value() === $other->value();
    }
}
