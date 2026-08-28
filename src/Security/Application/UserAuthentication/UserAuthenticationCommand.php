<?php

namespace App\Security\Application\UserAuthentication;

use App\Core\Application\Command\Command;

readonly class UserAuthenticationCommand implements Command
{
    public function __construct(
        public string $email,
        public string $password,
        public string $ip
    )
    {
    }
}
