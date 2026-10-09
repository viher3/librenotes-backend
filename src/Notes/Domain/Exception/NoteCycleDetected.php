<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class NoteCycleDetected extends DomainError
{
    public function __construct(
        private string $noteId,
        private string $targetId
    ) {
        parent::__construct(sprintf('Note %s cannot be placed under %s: it would contain itself', $noteId, $targetId));
    }

    public function errorCode(): string
    {
        return 'note.cycle_detected';
    }

    /**
     * @return array<string, string>
     */
    public function params(): array
    {
        return [
            'note_id' => $this->noteId,
            'target_id' => $this->targetId,
        ];
    }
}
