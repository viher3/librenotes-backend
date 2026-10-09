<?php

declare(strict_types=1);

namespace App\Notes\Application\AttachmentDetail;

use App\Core\Application\Query\QueryResponse;
use App\Notes\Domain\Model\Attachment;
use App\Shared\Domain\Time\Date;

class AttachmentDetailResponse implements QueryResponse
{
    public function __construct(
        private Attachment $attachment
    ) {
    }

    public function getResult(): array
    {
        return [
            'id' => $this->attachment->getId()->value(),
            'folder_id' => $this->attachment->getFolderId()?->value(),
            'note_id' => $this->attachment->getNoteId()?->value(),
            'file_name' => $this->attachment->getFileName()->value(),
            'mime_type' => $this->attachment->getMimeType()->value(),
            'size_bytes' => $this->attachment->getSizeBytes()->value(),
            'created_at' => $this->attachment->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
        ];
    }
}
