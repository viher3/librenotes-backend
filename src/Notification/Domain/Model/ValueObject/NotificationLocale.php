<?php

declare(strict_types=1);

namespace App\Notification\Domain\Model\ValueObject;

use App\Core\Domain\Exception\InvalidValueException;
use App\Core\Domain\ValueObject\StringValueObject;

final class NotificationLocale extends StringValueObject
{
    public const DEFAULT = 'en';

    private const VALID_REGEX = '/^[a-z]{2}(_[A-Z]{2})?$/';

    public function __construct(string $value)
    {
        if (!preg_match(self::VALID_REGEX, $value)) {
            throw new InvalidValueException($value);
        }

        parent::__construct($value);
    }

    public function equals(NotificationLocale $other): bool
    {
        return $this->value() === $other->value();
    }
}
