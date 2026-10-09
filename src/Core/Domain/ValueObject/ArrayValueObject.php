<?php

declare(strict_types=1);

namespace App\Core\Domain\ValueObject;

abstract class ArrayValueObject
{
    /**
     * @param array<int|string, mixed> $value
     */
    public function __construct(protected array $value)
    {
    }

    /**
     * @return array<int|string, mixed>
     */
    public function value(): array
    {
        return $this->value;
    }
}
