<?php

namespace App\Security\Application\RenewUserAuthentication;

use App\Core\Application\Command\CommandResponse;

class RenewUserAuthenticationResponse implements CommandResponse
{
    public function __construct(
        private string $accessToken,
        private string $refreshToken
    )
    {
    }

    public function getResult(): array
    {
        return [
            'accessToken' => $this->accessToken,
            'refreshToken' => $this->refreshToken,
        ];
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }
}
