<?php

declare(strict_types=1);

namespace App\Notes\Application\CreateLink;

use App\Core\Application\Command\Command;

readonly class CreateLinkCommand implements Command
{
    public function __construct(
        public string $id,
        public string $title,
        public string $url,
        public ?string $note,
        public ?string $folderId,
        public string $ownerId,
        /** @var string[] */
        public array $tags = []
    ) {
    }
}
