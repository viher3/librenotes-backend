<?php

namespace App\Core\Domain\ValueObject\ContactData;

use App\Core\Domain\Exception\InvalidValueException;
use App\Core\Domain\ValueObject\StringValueObject;

class EmailValueObject extends StringValueObject
{
    public const VALID_REGEX = '/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,6})+$/';

    public function __construct(string $value)
    {
        $this->value = $value;
        $this->assertIsValid();
    }

    private function assertIsValid(): void
    {
        if (!preg_match(self::VALID_REGEX, $this->value)) {
            throw new InvalidValueException($this->value);
        }
    }
}
