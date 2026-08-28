<?php

namespace App\Security\Application\RenewUserAuthentication;

use App\Core\Application\Command\Command;

readonly class RenewUserAuthenticationCommand implements Command
{
    public function __construct(
        public string $token,
        public string $ip
    )
    {
    }
}
