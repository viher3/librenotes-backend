<?php

declare(strict_types=1);

namespace App\Notes\Application\BulkMoveItems;

use App\Core\Application\Command\Command;
use App\Notes\Domain\ValueObject\ItemRef;

readonly class BulkMoveItemsCommand implements Command
{
    /**
     * @param ItemRef[] $items
     */
    public function __construct(
        public array $items,
        public ?string $folderId,
        public ?string $parentNoteId,
        public string $ownerId
    ) {
    }
}
