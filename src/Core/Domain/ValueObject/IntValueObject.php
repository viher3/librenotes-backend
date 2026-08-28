<?php

declare(strict_types=1);

namespace App\Core\Domain\ValueObject;

abstract class IntValueObject
{
    protected int $value;

    /**
     * @param integer $value
     */
    public function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @return integer
     */
    public function value(): int
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value();
    }
}
