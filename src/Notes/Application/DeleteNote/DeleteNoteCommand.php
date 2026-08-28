<?php

declare(strict_types=1);

namespace App\Notes\Application\DeleteNote;

use App\Core\Application\Command\Command;

readonly class DeleteNoteCommand implements Command
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
