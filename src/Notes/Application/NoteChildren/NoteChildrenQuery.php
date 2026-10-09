<?php

declare(strict_types=1);

namespace App\Notes\Application\NoteChildren;

use App\Core\Application\Query\Query;

readonly class NoteChildrenQuery implements Query
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
