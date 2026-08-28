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
