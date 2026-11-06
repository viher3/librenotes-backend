<?php

declare(strict_types=1);

namespace App\Notes\Domain\Service;

use App\Notes\Domain\Exception\AttachmentNotFound;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\LinkNotFound;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Model\SelectedItems;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\ItemRef;
use App\Notes\Domain\ValueObject\ItemType;
use App\Notes\Domain\ValueObject\OwnerId;

/**
 * Turns the ids a user selected into the items to act on: every one must exist, be the user's and not be in the
 * trash, repeated ones count once, and anything that sits inside another selected item is left out, because it
 * travels with it (so the result does not depend on the order of the selection).
 */
class ItemSelection
{
    /** Guards against corrupt data: a legitimate tree is never this deep. */
    private const int MAX_DEPTH = 100;

    public function __construct(
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository,
        private LinkRepository $linkRepository,
        private AttachmentRepository $attachmentRepository
    ) {
    }

    /**
     * @param ItemRef[] $refs
     *
     * @throws FolderNotFound
     * @throws NoteNotFound
     * @throws LinkNotFound
     * @throws AttachmentNotFound
     */
    public function select(array $refs, OwnerId $ownerId): SelectedItems
    {
        $folders = $notes = $links = $attachments = [];

        foreach ($refs as $ref) {
            match ($ref->type) {
                ItemType::Folder => $folders[$ref->id] = $this->folder($ref->id, $ownerId),
                ItemType::Note => $notes[$ref->id] = $this->note($ref->id, $ownerId),
                ItemType::Link => $links[$ref->id] = $this->link($ref->id, $ownerId),
                ItemType::Attachment => $attachments[$ref->id] = $this->attachment($ref->id, $ownerId),
            };
        }

        $selectedFolders = array_fill_keys(array_keys($folders), true);
        $selectedNotes = array_fill_keys(array_keys($notes), true);
        $inside = fn (?string $folderId, ?string $noteId): bool => $this->isInsideSelected($folderId, $noteId, $selectedFolders, $selectedNotes);

        return new SelectedItems(
            array_values(array_filter($folders, fn ($f) => !$inside($f->getParentFolderId()?->value(), null))),
            array_values(array_filter($notes, fn ($n) => !$inside($n->getFolderId()?->value(), $n->getParentNoteId()?->value()))),
            array_values(array_filter($links, fn ($l) => !$inside($l->getFolderId()?->value(), $l->getParentNoteId()?->value()))),
            array_values(array_filter($attachments, fn ($a) => !$inside($a->getFolderId()?->value(), $a->getNoteId()?->value()))),
        );
    }

    /**
     * Whether something placed in that folder or under that note ends up inside a selected folder or note.
     *
     * @param array<string, true> $selectedFolders
     * @param array<string, true> $selectedNotes
     */
    private function isInsideSelected(?string $folderId, ?string $noteId, array $selectedFolders, array $selectedNotes): bool
    {
        for ($depth = 0; $depth < self::MAX_DEPTH; $depth++) {
            if ($noteId !== null) {
                if (isset($selectedNotes[$noteId])) {
                    return true;
                }
                $note = $this->noteRepository->getById($noteId);
                if ($note === null) {
                    return false;
                }
                $folderId = $note->getFolderId()?->value();
                $noteId = $note->getParentNoteId()?->value();
                continue;
            }

            if ($folderId !== null) {
                if (isset($selectedFolders[$folderId])) {
                    return true;
                }
                $folder = $this->folderRepository->getById($folderId);
                if ($folder === null) {
                    return false;
                }
                $folderId = $folder->getParentFolderId()?->value();
                continue;
            }

            return false;
        }

        return false;
    }

    private function folder(string $id, OwnerId $ownerId): \App\Notes\Domain\Model\Folder
    {
        $folder = $this->folderRepository->getById($id);
        if ($folder === null || !$folder->belongsTo($ownerId) || $folder->isDeleted()) {
            throw new FolderNotFound($id);
        }

        return $folder;
    }

    private function note(string $id, OwnerId $ownerId): \App\Notes\Domain\Model\Note
    {
        $note = $this->noteRepository->getById($id);
        if ($note === null || !$note->belongsTo($ownerId) || $note->isDeleted()) {
            throw new NoteNotFound($id);
        }

        return $note;
    }

    private function link(string $id, OwnerId $ownerId): \App\Notes\Domain\Model\Link
    {
        $link = $this->linkRepository->getById($id);
        if ($link === null || !$link->belongsTo($ownerId) || $link->isDeleted()) {
            throw new LinkNotFound($id);
        }

        return $link;
    }

    private function attachment(string $id, OwnerId $ownerId): \App\Notes\Domain\Model\Attachment
    {
        $attachment = $this->attachmentRepository->getById($id);
        if ($attachment === null || !$attachment->belongsTo($ownerId) || $attachment->isDeleted()) {
            throw new AttachmentNotFound($id);
        }

        return $attachment;
    }
}
