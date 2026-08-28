<?php

declare(strict_types=1);

namespace App\Core\Domain\ValueObject;

class DateTimeValueObject extends DateValueObject
{
    public function __construct(\DateTimeInterface $value)
    {
        parent::__construct(\DateTimeImmutable::createFromInterface($value));
    }

    public function equals(DateTimeValueObject $other): bool
    {
        return $this->value() == $other->value();
    }

    public function isBefore(DateTimeValueObject $other): bool
    {
        return $this->value() < $other->value();
    }

    public function isAfter(DateTimeValueObject $other): bool
    {
        return $this->value() > $other->value();
    }
}
