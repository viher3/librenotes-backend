<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class AttachmentEmpty extends DomainError
{
    public function __construct()
    {
        parent::__construct('Empty files cannot be attached');
    }

    public function errorCode(): string
    {
        return 'attachment.empty';
    }

    /**
     * @return array<string, mixed>
     */
    public function params(): array
    {
        return [];
    }
}
