<?php

declare(strict_types=1);

namespace App\Notes\Application\PermanentlyDeleteNote;

use App\Core\Application\Command\Command;

readonly class PermanentlyDeleteNoteCommand implements Command
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
