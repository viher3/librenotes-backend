<?php

declare(strict_types=1);

namespace App\Notes\Domain\Service;

use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\InvalidContentLocation;
use App\Notes\Domain\Exception\ParentNoteNotFound;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\NoteId;
use App\Notes\Domain\ValueObject\OwnerId;

/** Checks where a note or link is being put: a folder, a parent note, or the root. */
class ContentPlacement
{
    public function __construct(
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository
    ) {
    }

    /**
     * @return array{0: ?FolderId, 1: ?NoteId} the folder and the parent note (at most one of them is set)
     *
     * @throws InvalidContentLocation both were given
     * @throws FolderNotFound         the folder is missing, foreign or in the trash
     * @throws ParentNoteNotFound     the parent note is missing, foreign or in the trash
     */
    public function resolve(?string $folderId, ?string $parentNoteId, OwnerId $ownerId): array
    {
        if ($folderId !== null && $parentNoteId !== null) {
            throw new InvalidContentLocation();
        }

        if ($folderId !== null) {
            $folder = $this->folderRepository->getById($folderId);
            if ($folder === null || !$folder->belongsTo($ownerId) || $folder->isDeleted()) {
                throw new FolderNotFound($folderId);
            }

            return [new FolderId($folderId), null];
        }

        if ($parentNoteId !== null) {
            $parent = $this->noteRepository->getById($parentNoteId);
            if ($parent === null || !$parent->belongsTo($ownerId) || $parent->isDeleted()) {
                throw new ParentNoteNotFound($parentNoteId);
            }

            return [null, new NoteId($parentNoteId)];
        }

        return [null, null];
    }
}
