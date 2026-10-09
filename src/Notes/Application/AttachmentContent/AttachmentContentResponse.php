<?php

declare(strict_types=1);

namespace App\Notes\Application\AttachmentContent;

use App\Core\Application\Query\QueryResponse;
use App\Notes\Domain\Model\Attachment;

class AttachmentContentResponse implements QueryResponse
{
    /**
     * @param resource $stream
     */
    public function __construct(
        private Attachment $attachment,
        private $stream
    ) {
    }

    public function getAttachment(): Attachment
    {
        return $this->attachment;
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return [
            'file_name' => $this->attachment->getFileName()->value(),
            'mime_type' => $this->attachment->getMimeType()->value(),
            'size_bytes' => $this->attachment->getSizeBytes()->value(),
        ];
    }
}
