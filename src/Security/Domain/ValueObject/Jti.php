<?php

declare(strict_types=1);

namespace App\Security\Domain\ValueObject;

use App\Core\Domain\ValueObject\StringValueObject;

/**
 * Wraps both the "jti" and "replaced_by_jti" columns; the latter is an empty
 * string (not null) until the token is replaced, so this VO allows blank values.
 */
class Jti extends StringValueObject
{
}
