<?php

declare(strict_types=1);

namespace App\Notes\Application\NoteChildren;

use App\Core\Application\Query\QueryResponse;
use App\Notes\Domain\Model\Link;
use App\Notes\Domain\Model\Note;
use App\Shared\Domain\Time\Date;

class NoteChildrenResponse implements QueryResponse
{
    /**
     * @param Note[] $notes
     * @param array<string, int> $childCounts live children (notes and links) per child note id
     * @param Link[] $links
     */
    public function __construct(
        private array $notes,
        private array $childCounts,
        private array $links
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return [
            'notes' => array_map(
                fn (Note $note) => [
                    'id' => $note->getId()->value(),
                    'title' => $note->getTitle()->value(),
                    'icon' => $note->getIcon()?->value(),
                    'pinned' => $note->isPinned(),
                    'tags' => $note->getTags()->names(),
                    'child_count' => $this->childCounts[$note->getId()->value()] ?? 0,
                    'created_at' => $note->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                    'updated_at' => $note->getUpdatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->notes
            ),
            'links' => array_map(
                static fn (Link $link) => [
                    'id' => $link->getId()->value(),
                    'title' => $link->getTitle()->value(),
                    'icon' => $link->getIcon()?->value(),
                    'url' => $link->getUrl()->value(),
                    'note' => $link->getNote()?->value(),
                    'tags' => $link->getTags()->names(),
                    'created_at' => $link->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                    'updated_at' => $link->getUpdatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->links
            ),
        ];
    }
}
