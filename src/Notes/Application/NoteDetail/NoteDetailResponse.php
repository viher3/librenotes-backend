<?php

declare(strict_types=1);

namespace App\Notes\Application\NoteDetail;

use App\Core\Application\Query\QueryResponse;
use App\Notes\Domain\Model\Note;
use App\Shared\Domain\Time\Date;

class NoteDetailResponse implements QueryResponse
{
    public function __construct(
        private Note $note
    ) {
    }

    public function getResult(): array
    {
        return [
            'id' => $this->note->getId()->value(),
            'title' => $this->note->getTitle()->value(),
            'content' => $this->note->getContent()->value(),
            'folderId' => $this->note->getFolderId()?->value(),
            'createdAt' => $this->note->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
            'updatedAt' => $this->note->getUpdatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
        ];
    }
}
