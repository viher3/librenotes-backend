<?php

declare(strict_types=1);

namespace App\Notes\Application\EmptyTrash;

use App\Core\Application\Command\Command;

readonly class EmptyTrashCommand implements Command
{
    public function __construct(
        public string $ownerId
    ) {
    }
}
