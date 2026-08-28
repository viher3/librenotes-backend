<?php

declare(strict_types=1);

namespace App\Notes\Application\Search;

use App\Core\Application\Query\QueryResponse;
use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Model\Note;

final class NotesSearchResponse implements QueryResponse
{
    /**
     * @param array{items: Note[], total: int} $notes
     * @param array{items: Folder[], total: int} $folders
     * @param array{items: Attachment[], total: int} $attachments
     */
    public function __construct(
        private readonly string $query,
        private readonly array $notes,
        private readonly array $folders,
        private readonly array $attachments
    ) {
    }

    /**
     * @return array{
     *     meta: array{query: string},
     *     data: array{
     *         notes: array{total: int, items: list<array<string, mixed>>},
     *         folders: array{total: int, items: list<array<string, mixed>>},
     *         attachments: array{total: int, items: list<array<string, mixed>>}
     *     }
     * }
     */
    public function getResult(): array
    {
        return [
            'meta' => [
                'query' => $this->query,
            ],
            'data' => [
                'notes' => [
                    'total' => $this->notes['total'],
                    'items' => array_map(
                        static fn (Note $note) => [
                            'id' => $note->getId()->value(),
                            'title' => $note->getTitle()->value(),
                            'folder_id' => $note->getFolderId()?->value(),
                            'updated_at' => $note->getUpdatedAt()->value()->format(DATE_ATOM),
                        ],
                        $this->notes['items']
                    ),
                ],
                'folders' => [
                    'total' => $this->folders['total'],
                    'items' => array_map(
                        static fn (Folder $folder) => [
                            'id' => $folder->getId()->value(),
                            'name' => $folder->getName()->value(),
                            'parent_folder_id' => $folder->getParentFolderId()?->value(),
                        ],
                        $this->folders['items']
                    ),
                ],
                'attachments' => [
                    'total' => $this->attachments['total'],
                    'items' => array_map(
                        static fn (Attachment $attachment) => [
                            'id' => $attachment->getId()->value(),
                            'file_name' => $attachment->getFileName()->value(),
                            'mime_type' => $attachment->getMimeType()->value(),
                            'folder_id' => $attachment->getFolderId()->value(),
                        ],
                        $this->attachments['items']
                    ),
                ],
            ],
        ];
    }
}
