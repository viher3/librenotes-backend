<?php

declare(strict_types=1);

namespace App\Core\Domain\ValueObject;

use App\Core\Domain\Exception\InvalidValueException;

class String255MaxLengthValueObject extends StringValueObject
{
    public function __construct(string $value)
    {
        if (strlen($value) < 1 || strlen($value) > 255) {
            throw new InvalidValueException($value);
        }

        parent::__construct($value);
    }
}
