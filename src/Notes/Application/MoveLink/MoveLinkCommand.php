<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveLink;

use App\Core\Application\Command\Command;

readonly class MoveLinkCommand implements Command
{
    public function __construct(
        public string $id,
        public ?string $folderId,
        public string $ownerId,
        public ?string $parentNoteId = null
    ) {
    }
}
