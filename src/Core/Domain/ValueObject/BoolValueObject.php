<?php

declare(strict_types=1);

namespace App\Core\Domain\ValueObject;

class BoolValueObject
{
    public function __construct(protected bool $value)
    {
    }

    public function value(): bool
    {
        return $this->value;
    }

    public function equals(BoolValueObject $other): bool
    {
        return $this->value === $other->value();
    }

    public function __toString(): string
    {
        return $this->value ? '1' : '0';
    }
}
