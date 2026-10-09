<?php

declare(strict_types=1);

namespace App\Notes\Application\ListTags;

use App\Core\Application\Query\QueryResponse;

class ListTagsResponse implements QueryResponse
{
    /**
     * @param list<array{name: string, count: int}> $tags
     */
    public function __construct(
        private array $tags
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return ['data' => $this->tags];
    }
}
