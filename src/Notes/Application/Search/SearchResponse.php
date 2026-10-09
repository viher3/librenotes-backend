<?php

declare(strict_types=1);

namespace App\Notes\Application\Search;

use App\Core\Application\Query\QueryResponse;

class SearchResponse implements QueryResponse
{
    /**
     * @param list<array<string, mixed>> $items
     */
    public function __construct(
        private array $items,
        private int $total,
        private int $page,
        private int $size
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return [
            'meta' => [
                'page' => $this->page,
                'size' => $this->size,
                'total' => $this->total,
                'last_page' => (int) ceil($this->total / $this->size),
            ],
            'data' => $this->items,
        ];
    }
}
