<?php

declare(strict_types=1);

namespace App\Notes\Application\DeleteFolder;

use App\Core\Application\Command\Command;

readonly class DeleteFolderCommand implements Command
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
