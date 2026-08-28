<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class NotInTrash extends DomainError
{
    public function __construct(
        private string $value
    ) {
        parent::__construct('Not in trash: ' . $this->value);
    }

    public function errorCode(): string
    {
        return 'notes.not_in_trash';
    }

    public function params(): array
    {
        return [
            'value' => $this->value,
        ];
    }
}
