<?php

declare(strict_types=1);

namespace App\Notes\Application\UploadAttachment;

use App\Core\Application\Command\Command;

readonly class UploadAttachmentCommand implements Command
{
    public function __construct(
        public string $id,
        public ?string $folderId,
        public ?string $noteId,
        public string $ownerId,
        public string $originalFileName,
        public string $mimeType,
        public int $sizeBytes,
        public string $tmpFilePath
    ) {
    }
}
