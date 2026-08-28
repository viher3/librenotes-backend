<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Model\ValueObject;

use App\Core\Domain\ValueObject\StringValueObject;

/**
 * Opaque storage form of a configuration value: plaintext when the configuration
 * is not sensitive, or the encrypted blob (base64 nonce+ciphertext) when it is.
 * Casting to the declared PHP type and decryption both happen outside this VO.
 */
final class ConfigurationValue extends StringValueObject
{
}
