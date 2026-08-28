<?php

namespace App\Security\Domain\Service;

interface TokenEncoderDecoder
{
    public function encode(array $payload): string;

    public function decode(string $token): array;

    public function decodeWithoutVerification(string $token) : array;
}