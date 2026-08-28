<?php

declare(strict_types=1);

namespace App\Notes\Application\UpdateNote;

use App\Core\Application\Command\Command;

readonly class UpdateNoteCommand implements Command
{
    public function __construct(
        public string $id,
        public array $fields,
        public string $ownerId
    ) {
    }
}
