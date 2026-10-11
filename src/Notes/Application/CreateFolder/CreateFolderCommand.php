<?php

declare(strict_types=1);

namespace App\Notes\Application\CreateFolder;

use App\Core\Application\Command\Command;

readonly class CreateFolderCommand implements Command
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $parentFolderId,
        public string $ownerId,
        public ?string $icon = null
    ) {
    }
}
