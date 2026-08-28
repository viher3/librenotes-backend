<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Service;

interface ConfigurationValueEncryptor
{
    public function encrypt(string $plaintext): EncryptedValue;

    public function decrypt(string $ciphertext, int $keyVersion): string;

    public function currentKeyVersion(): int;
}
