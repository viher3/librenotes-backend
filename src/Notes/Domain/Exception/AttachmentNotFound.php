<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\NotFoundException;

class AttachmentNotFound extends NotFoundException
{
    public function __construct(
        private string $value
    ) {
        parent::__construct($this->value);
    }

    public function errorMessage(): string
    {
        return 'Attachment not found: ' . $this->value;
    }

    public function errorCode(): string
    {
        return 'attachment.not_found';
    }
}
