<?php

declare(strict_types=1);

namespace App\Notes\Application\CreateNote;

use App\Core\Application\Command\Command;

readonly class CreateNoteCommand implements Command
{
    public function __construct(
        public string $id,
        public string $title,
        public string $content,
        public ?string $folderId,
        public string $ownerId,
        public bool $pinned = false,
        /** @var string[] */
        public array $tags = [],
        public ?string $parentNoteId = null,
        public ?string $icon = null
    ) {
    }
}
