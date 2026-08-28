<?php

declare(strict_types=1);

namespace App\User\Application\Create;

use App\Core\Application\Command\Command;
use App\User\Domain\UserRoleValue;

readonly class CreateUserCommand implements Command
{
    public function __construct(
        public string $id,
        public string $email,
        public string $password,
        public ?string $username=null,
        public bool $isActive = true,
        public bool $isEmailValidated = true
    ) {
    }
}
