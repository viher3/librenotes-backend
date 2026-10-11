<?php

declare(strict_types=1);

namespace App\Notes\Application\ListTrash;

use App\Core\Application\Query\QueryResponse;
use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Model\Link;
use App\Notes\Domain\Model\Note;
use App\Shared\Domain\Time\Date;

class ListTrashResponse implements QueryResponse
{
    /**
     * @param Folder[] $folders
     * @param Note[] $notes
     * @param Link[] $links
     * @param Attachment[] $attachments
     */
    public function __construct(
        private array $folders,
        private array $notes,
        private array $links,
        private array $attachments
    ) {
    }

    public function getResult(): array
    {
        return [
            'folders' => array_map(
                static fn (Folder $folder) => [
                    'id' => $folder->getId()->value(),
                    'name' => $folder->getName()->value(),
                    'icon' => $folder->getIcon()?->value(),
                    'parent_folder_id' => $folder->getParentFolderId()?->value(),
                    'deleted_at' => $folder->getDeletedAt()?->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->folders
            ),
            'notes' => array_map(
                static fn (Note $note) => [
                    'id' => $note->getId()->value(),
                    'title' => $note->getTitle()->value(),
                    'icon' => $note->getIcon()?->value(),
                    'folder_id' => $note->getFolderId()?->value(),
                    'deleted_at' => $note->getDeletedAt()?->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->notes
            ),
            'links' => array_map(
                static fn (Link $link) => [
                    'id' => $link->getId()->value(),
                    'title' => $link->getTitle()->value(),
                    'icon' => $link->getIcon()?->value(),
                    'url' => $link->getUrl()->value(),
                    'folder_id' => $link->getFolderId()?->value(),
                    'deleted_at' => $link->getDeletedAt()?->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->links
            ),
            'attachments' => array_map(
                static fn (Attachment $attachment) => [
                    'id' => $attachment->getId()->value(),
                    'file_name' => $attachment->getFileName()->value(),
                    'folder_id' => $attachment->getFolderId()?->value(),
                    'note_id' => $attachment->getNoteId()?->value(),
                    'deleted_at' => $attachment->getDeletedAt()?->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->attachments
            ),
        ];
    }
}
