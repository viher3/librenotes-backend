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
            'folderId' => $this->attachment->getFolderId()->value(),
            'fileName' => $this->attachment->getFileName()->value(),
            'mimeType' => $this->attachment->getMimeType()->value(),
            'sizeBytes' => $this->attachment->getSizeBytes()->value(),
            'createdAt' => $this->attachment->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
        ];
    }
}
