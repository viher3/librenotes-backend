<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveFolderToTrash;

use App\Core\Application\Command\Command;

readonly class MoveFolderToTrashCommand implements Command
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
