<?php

namespace App\Security\Application\UserAuthentication;

use App\Core\Application\Command\CommandResponse;

class UserAuthenticationResponse implements CommandResponse
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
}
