<?php

declare(strict_types=1);

namespace App\Notes\Application\RestoreNote;

use App\Core\Application\Command\Command;

readonly class RestoreNoteCommand implements Command
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
