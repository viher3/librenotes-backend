<?php

declare(strict_types=1);

namespace App\Notes\Application\BulkItems;

use App\Core\Application\Command\CommandResponse;

/** How many top-level items a bulk action touched (items inside other selected items count with their container). */
readonly class BulkItemsResponse implements CommandResponse
{
    public function __construct(
        private int $count
    ) {
    }

    /**
     * @return array{count: int}
     */
    public function getResult(): array
    {
        return ['count' => $this->count];
    }
}
