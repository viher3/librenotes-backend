<?php

namespace App\Security\Domain\Service;

interface JwtKeyReader
{
    public function getPublicKey(string $filepath): string;

    public function getPrivateKey(string $filepath): string;
}