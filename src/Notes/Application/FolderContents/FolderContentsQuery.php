<?php

declare(strict_types=1);

namespace App\Notes\Application\FolderContents;

use App\Core\Application\Query\Query;

readonly class FolderContentsQuery implements Query
{
    public function __construct(
        public ?string $folderId,
        public string $ownerId
    ) {
    }
}
