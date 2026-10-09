<?php

declare(strict_types=1);

namespace App\Notes\Application\ListTags;

use App\Core\Application\Query\Query;

readonly class ListTagsQuery implements Query
{
    public function __construct(
        public string $ownerId
    ) {
    }
}
