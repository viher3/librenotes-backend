<?php

namespace App\User\Application\ChangePassword;

use App\Core\Application\Command\Command;

readonly class ChangePasswordCommand implements Command
{
    public function __construct(
        public string $userId,
        public string $oldPassword,
        public string $newPassword,
    )
    {
    }
}