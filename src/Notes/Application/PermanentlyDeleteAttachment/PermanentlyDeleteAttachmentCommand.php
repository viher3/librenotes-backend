<?php

declare(strict_types=1);

namespace App\Notes\Application\PermanentlyDeleteAttachment;

use App\Core\Application\Command\Command;

readonly class PermanentlyDeleteAttachmentCommand implements Command
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
