<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Service;

final readonly class EncryptedValue
{
    public function __construct(
        public string $ciphertext,
        public int $keyVersion
    ) {
    }
}
