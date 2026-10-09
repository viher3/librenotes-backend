<?php

declare(strict_types=1);

namespace App\Notes\Application\Search;

use App\Core\Application\Query\Query;

readonly class SearchQuery implements Query
{
    /**
     * @param string[] $types
     */
    public function __construct(
        public string $ownerId,
        public string $q,
        public array $types,
        public ?string $tag,
        public ?int $page,
        public ?int $size
    ) {
    }
}
