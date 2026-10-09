<?php

declare(strict_types=1);

namespace App\Notes\Application\RestoreLink;

use App\Core\Application\Command\Command;

readonly class RestoreLinkCommand implements Command
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
