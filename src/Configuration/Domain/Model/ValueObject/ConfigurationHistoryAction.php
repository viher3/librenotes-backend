<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Model\ValueObject;

use App\Core\Domain\Exception\InvalidValueException;
use App\Core\Domain\ValueObject\StringValueObject;

final class ConfigurationHistoryAction extends StringValueObject
{
    public const CREATED = 'created';
    public const UPDATED = 'updated';
    public const DELETED = 'deleted';

    private const VALID_ACTIONS = [
        self::CREATED,
        self::UPDATED,
        self::DELETED,
    ];

    public function __construct(string $value)
    {
        if (!in_array($value, self::VALID_ACTIONS, true)) {
            throw new InvalidValueException($value);
        }

        parent::__construct($value);
    }
}
