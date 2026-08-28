<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Model\ValueObject;

use App\Configuration\Domain\Exception\InvalidConfigurationTypeException;
use App\Core\Domain\ValueObject\StringValueObject;

final class ConfigurationType extends StringValueObject
{
    public const STRING = 'string';
    public const INTEGER = 'integer';
    public const BOOLEAN = 'boolean';
    public const JSON = 'json';

    private const VALID_TYPES = [
        self::STRING,
        self::INTEGER,
        self::BOOLEAN,
        self::JSON,
    ];

    public function __construct(string $value)
    {
        if (!in_array($value, self::VALID_TYPES, true)) {
            throw new InvalidConfigurationTypeException($value);
        }

        parent::__construct($value);
    }

    public function equals(ConfigurationType $other): bool
    {
        return $this->value() === $other->value();
    }
}
