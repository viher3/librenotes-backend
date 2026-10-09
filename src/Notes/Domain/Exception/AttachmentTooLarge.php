<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class AttachmentTooLarge extends DomainError
{
    public function __construct(
        private int $maxBytes
    ) {
        parent::__construct('Attachment exceeds the maximum size of ' . $this->maxBytes . ' bytes');
    }

    public function errorCode(): string
    {
        return 'attachment.too_large';
    }

    /**
     * @return array<string, int>
     */
    public function params(): array
    {
        return [
            'max_bytes' => $this->maxBytes,
        ];
    }
}
