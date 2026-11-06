<?php

declare(strict_types=1);

namespace App\Notes\Application\BulkMoveItemsToTrash;

use App\Core\Application\Command\Command;
use App\Notes\Domain\ValueObject\ItemRef;

readonly class BulkMoveItemsToTrashCommand implements Command
{
    /**
     * @param ItemRef[] $items
     */
    public function __construct(
        public array $items,
        public string $ownerId
    ) {
    }
}
