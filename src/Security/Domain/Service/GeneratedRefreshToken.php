<?php

declare(strict_types=1);

namespace App\Security\Domain\Service;

readonly class GeneratedRefreshToken
{
    public function __construct(
        public string $value,
        public string $jti,
    ) {
    }
}
