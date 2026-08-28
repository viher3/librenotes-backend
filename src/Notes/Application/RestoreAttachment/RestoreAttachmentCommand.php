<?php

declare(strict_types=1);

namespace App\Notes\Application\RestoreAttachment;

use App\Core\Application\Command\Command;

readonly class RestoreAttachmentCommand implements Command
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
