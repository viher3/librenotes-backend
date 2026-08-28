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
     * @return Note[]
     */
    public function findByFolder(?FolderId $folderId, OwnerId $ownerId): array;

    public function delete(Note $note): void;
}
