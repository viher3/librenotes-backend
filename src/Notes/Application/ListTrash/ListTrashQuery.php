<?php

declare(strict_types=1);

namespace App\Notes\Application\ListTrash;

use App\Core\Application\Query\Query;

readonly class ListTrashQuery implements Query
{
    public function __construct(
        public string $ownerId
    ) {
    }
}
