<?php

declare(strict_types=1);

namespace App\Notes\Application\DeleteAttachment;

use App\Core\Application\Command\Command;

readonly class DeleteAttachmentCommand implements Command
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
