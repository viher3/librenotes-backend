<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class ParentNoteInTrash extends DomainError
{
    public function __construct(
        private string $value
    ) {
        parent::__construct('Parent note is in trash: ' . $this->value);
    }

    public function errorCode(): string
    {
        return 'notes.parent_note_in_trash';
    }

    /**
     * @return array<string, string>
     */
    public function params(): array
    {
        return [
            'value' => $this->value,
        ];
    }
}
