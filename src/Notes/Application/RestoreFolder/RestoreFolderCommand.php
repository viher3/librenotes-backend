<?php

declare(strict_types=1);

namespace App\Notes\Application\RestoreFolder;

use App\Core\Application\Command\Command;

readonly class RestoreFolderCommand implements Command
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
