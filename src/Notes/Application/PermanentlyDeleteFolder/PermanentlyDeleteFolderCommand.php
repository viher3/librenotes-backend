<?php

declare(strict_types=1);

namespace App\Notes\Application\PermanentlyDeleteFolder;

use App\Core\Application\Command\Command;

readonly class PermanentlyDeleteFolderCommand implements Command
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
