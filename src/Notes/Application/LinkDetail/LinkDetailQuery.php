<?php

declare(strict_types=1);

namespace App\Notes\Application\LinkDetail;

use App\Core\Application\Query\Query;

readonly class LinkDetailQuery implements Query
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
