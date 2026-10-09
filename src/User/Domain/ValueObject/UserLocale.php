<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Core\Domain\ValueObject\StringValueObject;
use App\User\Domain\Exception\UnsupportedUserLocale;

class UserLocale extends StringValueObject
{
    public const string DEFAULT = 'en';

    /** @var string[] */
    public const array SUPPORTED = ['en', 'es'];

    public function __construct(string $value)
    {
        if (!in_array($value, self::SUPPORTED, true)) {
            throw new UnsupportedUserLocale($value);
        }

        parent::__construct($value);
    }

    public static function default(): self
    {
        return new self(self::DEFAULT);
    }
}
