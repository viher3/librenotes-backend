<?php

declare(strict_types=1);

namespace App\Notes\Application\ListNotes;

use App\Core\Application\Query\Query;

readonly class ListNotesQuery implements Query
{
    public function __construct(
        public string $ownerId,
        public ?string $folderId,
        public bool $rootOnly,
        public ?string $tag,
        public ?bool $pinned,
        public ?string $orderBy,
        public ?string $orderDirection,
        public ?int $page,
        public ?int $size
    ) {
    }
}
