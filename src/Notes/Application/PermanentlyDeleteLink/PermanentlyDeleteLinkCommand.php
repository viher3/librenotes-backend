<?php

declare(strict_types=1);

namespace App\Notes\Application\PermanentlyDeleteLink;

use App\Core\Application\Command\Command;

readonly class PermanentlyDeleteLinkCommand implements Command
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
