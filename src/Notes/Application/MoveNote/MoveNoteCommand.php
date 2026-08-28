<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveNote;

use App\Core\Application\Command\Command;

readonly class MoveNoteCommand implements Command
{
    public function __construct(
        public string $id,
        public ?string $folderId,
        public string $ownerId
    ) {
    }
}
