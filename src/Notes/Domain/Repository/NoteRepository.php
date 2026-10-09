<?php

declare(strict_types=1);

namespace App\Notes\Domain\Repository;

use App\Notes\Domain\Model\Note;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;

interface NoteRepository
{
    public function save(Note $note): void;

    public function getById(string $id): ?Note;

    /**
     * Notes in the folder that are not in trash — backs normal browsing.
     *
     * @return Note[]
     */
    public function findByFolder(?FolderId $folderId, OwnerId $ownerId): array;

    /**
     * Notes in the folder regardless of trash state — backs trash/restore cascades.
     *
     * @return Note[]
     */
    public function findAllByFolder(?FolderId $folderId, OwnerId $ownerId): array;

    /**
     * One page of the owner's notes that are not in trash, plus the total number of matches.
     *
     * @return array{notes: Note[], total: int}
     */
    public function findPage(NoteListCriteria $criteria): array;

    /**
     * @return Note[]
     */
    public function findTrashed(OwnerId $ownerId): array;

    public function delete(Note $note): void;
}
