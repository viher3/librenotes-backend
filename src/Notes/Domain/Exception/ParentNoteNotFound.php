<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\NotFoundException;

/** The note chosen as a parent does not exist (or is not the user's, or is in the trash). */
class ParentNoteNotFound extends NotFoundException
{
    public function __construct(
        private string $value
    ) {
        parent::__construct($this->value);
    }

    public function errorMessage(): string
    {
        return 'Parent note not found: ' . $this->value;
    }

    public function errorCode(): string
    {
        return 'note.parent_not_found';
    }
}
