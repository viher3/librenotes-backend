<?php

namespace App\Security\Infrastructure;

use App\Security\Domain\Service\JwtKeyReader;

class FileJwtKeyReader implements JwtKeyReader
{
    public function getPublicKey(string $filepath): string
    {
        return file_get_contents($filepath);
    }

    public function getPrivateKey(string $filepath): string
    {
        return file_get_contents($filepath);
    }
}