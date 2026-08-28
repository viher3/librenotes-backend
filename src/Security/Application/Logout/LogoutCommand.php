<?php

declare(strict_types=1);

namespace App\Security\Application\Logout;

use App\Core\Application\Command\Command;

readonly class LogoutCommand implements Command
{
    public function __construct(
        public string $token
    )
    {
    }
}
