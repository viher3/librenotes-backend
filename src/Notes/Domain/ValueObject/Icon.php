<?php

declare(strict_types=1);

namespace App\Notes\Domain\ValueObject;

use App\Core\Domain\ValueObject\StringValueObject;
use App\Notes\Domain\Exception\InvalidIcon;

/**
 * The icon a user picked for a folder, note or link: exactly one emoji (a single user-perceived character, so
 * flags, skin tones and ZWJ sequences count as one). "No icon" is `null` on the owner, never an empty Icon.
 */
class Icon extends StringValueObject
{
    public const int MAX_LENGTH = 32;

    public function __construct(string $value)
    {
        if ($value === '' || mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidIcon(sprintf('an icon must have between 1 and %d characters', self::MAX_LENGTH));
        }

        if (preg_match_all('/\X/u', $value) !== 1) {
            throw new InvalidIcon('an icon must be a single emoji');
        }

        if (preg_match('/\p{Extended_Pictographic}|\p{Regional_Indicator}|^[0-9#*]\x{FE0F}?\x{20E3}$/u', $value) !== 1) {
            throw new InvalidIcon('an icon must be an emoji');
        }

        parent::__construct($value);
    }

    /**
     * An absent, null or empty value means "use the default icon".
     */
    public static function fromNullable(?string $value): ?self
    {
        return ($value === null || $value === '') ? null : new self($value);
    }
}
