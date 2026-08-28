<?php

declare(strict_types=1);

namespace App\User\Application\ActivateAccount;

use App\Core\Application\Command\Command;

readonly class ActivateAccountCommand implements Command
{
    public function __construct(
        public string $token
    ) {
    }
}
