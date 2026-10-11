<?php

declare(strict_types=1);

namespace App\Notes\Application\FolderContents;

use App\Core\Application\Query\QueryResponse;
use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Model\Link;
use App\Notes\Domain\Model\Note;
use App\Shared\Domain\Time\Date;

class FolderContentsResponse implements QueryResponse
{
    /**
     * @param Folder[] $subfolders
     * @param Note[] $notes
     * @param array<string, int> $childCounts live children (notes and links) per note id
     * @param list<array{type: string, id: string, title: string}> $path ancestors of the folder, root first
     * @param Link[] $links
     * @param Attachment[] $attachments
     */
    public function __construct(
        private ?Folder $folder,
        private array $subfolders,
        private array $notes,
        private array $childCounts,
        private array $path,
        private array $links,
        private array $attachments
    ) {
    }

    public function getResult(): array
    {
        return [
            'folder' => $this->folder ? [
                'id' => $this->folder->getId()->value(),
                'name' => $this->folder->getName()->value(),
                'icon' => $this->folder->getIcon()?->value(),
                'parent_folder_id' => $this->folder->getParentFolderId()?->value(),
                'path' => $this->path,
            ] : null,
            'subfolders' => array_map(
                static fn (Folder $folder) => [
                    'id' => $folder->getId()->value(),
                    'name' => $folder->getName()->value(),
                    'icon' => $folder->getIcon()?->value(),
                    'created_at' => $folder->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->subfolders
            ),
            'notes' => array_map(
                fn (Note $note) => [
                    'id' => $note->getId()->value(),
                    'title' => $note->getTitle()->value(),
                    'icon' => $note->getIcon()?->value(),
                    'pinned' => $note->isPinned(),
                    'is_encrypted' => $note->isEncrypted(),
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
            'attachments' => array_map(
                static fn (Attachment $attachment) => [
                    'id' => $attachment->getId()->value(),
                    'file_name' => $attachment->getFileName()->value(),
                    'mime_type' => $attachment->getMimeType()->value(),
                    'size_bytes' => $attachment->getSizeBytes()->value(),
                    'created_at' => $attachment->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->attachments
            ),
        ];
    }
}
