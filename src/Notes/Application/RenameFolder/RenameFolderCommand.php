<?php

declare(strict_types=1);

namespace App\Notes\Application\RenameFolder;

use App\Core\Application\Command\Command;

readonly class RenameFolderCommand implements Command
{
    public function __construct(
        public string $id,
        public string $name,
        public string $ownerId
    ) {
    }
}
