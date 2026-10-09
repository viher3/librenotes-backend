<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

/** A note or link lives in a folder or under a note, never both. */
class InvalidContentLocation extends DomainError
{
    public function __construct()
    {
        parent::__construct('A note or link belongs either to a folder or to a note, not both');
    }

    public function errorCode(): string
    {
        return 'notes.invalid_location';
    }

    /**
     * @return array<string, mixed>
     */
    public function params(): array
    {
        return [];
    }
}
