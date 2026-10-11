<?php

declare(strict_types=1);

namespace App\Notes\Application\Search;

use App\Core\Application\Query\Query;

readonly class NotesSearchQuery implements Query
{
    /**
     * @param string[] $types
     */
    public function __construct(
        public string $term,
        public string $ownerId,
        public array $types = [],
        public int $page = 1,
        public int $perPage = 10
    ) {
    }
}
