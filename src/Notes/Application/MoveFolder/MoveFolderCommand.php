<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveFolder;

use App\Core\Application\Command\Command;

readonly class MoveFolderCommand implements Command
{
    public function __construct(
        public string $id,
        public ?string $parentFolderId,
        public string $ownerId
    ) {
    }
}
