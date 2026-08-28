<?php

namespace App\Security\Domain\Repository;

use App\Security\Domain\Model\RefreshToken;

interface RefreshTokenRepository
{
    public function save(RefreshToken $refreshToken) : void;

    public function findByTokenHash(string $tokenHash) : ?RefreshToken;

    /**
     * @return RefreshToken[]
     */
    public function findActiveByUserId(string $userId) : array;
}