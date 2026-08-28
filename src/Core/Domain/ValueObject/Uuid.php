<?php

declare(strict_types=1);

namespace App\Core\Domain\ValueObject;

use App\Core\Domain\Exception\InvalidArgumentException;
use Ramsey\Uuid\Uuid as RamseyUuid;

class Uuid
{
    protected string $value;

    final public function __construct(string $value)
    {
        $this->ensureIsValidUuid($value);

        $this->value = $value;
    }

    public static function random(): static
    {
        return new static(RamseyUuid::uuid4()->toString());
    }

    /**
     * @return string
     */
    public function value(): string
    {
        return $this->value;
    }

    private function ensureIsValidUuid(string $id): void
    {
        if (!RamseyUuid::isValid($id)) {
            throw new InvalidArgumentException(sprintf('%s: %s', static::class, $id));
        }
    }

    /**
     * @param Uuid $other
     * @return boolean
     */
    public function equals(Uuid $other): bool
    {
        return $this->value() === $other->value();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value();
    }
}
