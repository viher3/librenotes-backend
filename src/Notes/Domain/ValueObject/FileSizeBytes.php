<?php

declare(strict_types=1);

namespace App\Notes\Domain\ValueObject;

use App\Core\Domain\Exception\InvalidValueException;
use App\Core\Domain\ValueObject\IntValueObject;

class FileSizeBytes extends IntValueObject
{
    public const int MAX_ATTACHMENT_BYTES = 50 * 1024 * 1024;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new InvalidValueException((string) $value);
        }

        parent::__construct($value);
    }
}
