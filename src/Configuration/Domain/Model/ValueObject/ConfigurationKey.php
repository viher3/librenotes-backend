<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Model\ValueObject;

use App\Configuration\Domain\Exception\InvalidConfigurationKeyFormatException;
use App\Core\Domain\ValueObject\StringValueObject;

final class ConfigurationKey extends StringValueObject
{
    private const VALID_REGEX = '/^[a-z0-9]+(\.[a-z0-9_]+)*$/';

    public function __construct(string $value)
    {
        if (!preg_match(self::VALID_REGEX, $value)) {
            throw new InvalidConfigurationKeyFormatException($value);
        }

        parent::__construct($value);
    }
}
