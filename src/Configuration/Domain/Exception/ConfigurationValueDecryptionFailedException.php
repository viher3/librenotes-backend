<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Exception;

use App\Core\Domain\Exception\CriticalException;

/**
 * Thrown when a stored ciphertext cannot be decrypted: either it was tampered with,
 * or its key_version no longer has a matching key in the encryption keyring (a key
 * was removed before every row referencing it had been rotated).
 */
final class ConfigurationValueDecryptionFailedException extends CriticalException
{
    public function __construct(int $keyVersion)
    {
        parent::__construct('Failed to decrypt configuration value encrypted with key version ' . $keyVersion);
    }
}
