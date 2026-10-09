<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveLinkToTrash;

use App\Core\Application\Command\Command;

readonly class MoveLinkToTrashCommand implements Command
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
