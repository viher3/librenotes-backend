<?php

declare(strict_types=1);

namespace App\Notes\Domain\Repository;

use App\Notes\Domain\Model\Note;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\NoteId;
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
     * Notes directly under a note that are not in trash — backs browsing the tree.
     *
     * @return Note[]
     */
    public function findByParent(NoteId $parentId, OwnerId $ownerId): array;

    /**
     * Notes directly under a note regardless of trash state — backs trash/restore/delete cascades.
     *
     * @return Note[]
     */
    public function findAllByParent(NoteId $parentId, OwnerId $ownerId): array;

    /**
     * How many live children (notes and links) each of the given notes has; notes without any are 0.
     *
     * @param string[] $noteIds
     * @return array<string, int>
     */
    public function countChildren(OwnerId $ownerId, array $noteIds): array;

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

    /**
     * Notes not in trash whose title or content match the search term, ranked by relevance.
     *
     * @return Note[]
     */
    public function searchByTerm(string $term, OwnerId $ownerId, int $page, int $perPage): array;

    public function searchByTermCount(string $term, OwnerId $ownerId): int;

    public function delete(Note $note): void;
}
