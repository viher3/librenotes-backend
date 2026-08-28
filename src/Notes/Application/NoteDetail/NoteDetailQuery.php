<?php

declare(strict_types=1);

namespace App\Notes\Application\NoteDetail;

use App\Core\Application\Query\Query;

readonly class NoteDetailQuery implements Query
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
