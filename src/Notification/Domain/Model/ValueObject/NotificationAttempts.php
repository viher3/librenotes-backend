<?php

declare(strict_types=1);

namespace App\Notification\Domain\Model\ValueObject;

use App\Core\Domain\ValueObject\IntValueObject;

final class NotificationAttempts extends IntValueObject
{
    public function increment(): self
    {
        return new self($this->value() + 1);
    }
}
