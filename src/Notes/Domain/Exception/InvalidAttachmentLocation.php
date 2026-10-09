<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class InvalidAttachmentLocation extends DomainError
{
    public function __construct()
    {
        parent::__construct('An attachment belongs either to a folder or to a note, not both');
    }

    public function errorCode(): string
    {
        return 'attachment.invalid_location';
    }

    /**
     * @return array<string, mixed>
     */
    public function params(): array
    {
        return [];
    }
}
