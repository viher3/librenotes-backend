<?php

declare(strict_types=1);

namespace App\Notes\Application\UpdateFolder;

use App\Core\Application\Command\Command;

readonly class UpdateFolderCommand implements Command
{
    public function __construct(
        public string $id,
        /** @var array<string, mixed> only the fields to change (`name`, `icon`) */
        public array $fields,
        public string $ownerId
    ) {
    }
}
