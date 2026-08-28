<?php

declare(strict_types=1);

namespace App\User\Application\Delete;

use App\Core\Application\Command\Command;

readonly class DeleteUserCommand implements Command
{
    public function __construct(
        public string $id
    )
    {
    }
}
