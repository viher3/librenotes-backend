<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveAttachment;

use App\Core\Application\Command\Command;

readonly class MoveAttachmentCommand implements Command
{
    public function __construct(
        public string $id,
        public ?string $folderId,
        public ?string $noteId,
        public string $ownerId
    ) {
    }
}
