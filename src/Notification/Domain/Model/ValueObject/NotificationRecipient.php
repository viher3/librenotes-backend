<?php

declare(strict_types=1);

namespace App\Notification\Domain\Model\ValueObject;

use App\Core\Domain\Exception\InvalidValueException;
use App\Core\Domain\ValueObject\StringValueObject;

final class NotificationRecipient extends StringValueObject
{
    public function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new InvalidValueException($value);
        }

        parent::__construct($value);
    }
}
