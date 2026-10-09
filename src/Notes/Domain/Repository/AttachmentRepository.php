<?php

declare(strict_types=1);

namespace App\Notes\Domain\Repository;

use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\NoteId;
use App\Notes\Domain\ValueObject\OwnerId;

interface AttachmentRepository
{
    public function save(Attachment $attachment): void;

    public function getById(string $id): ?Attachment;

    /**
     * Attachments in the folder that are not in trash — backs normal browsing.
     *
     * @return Attachment[]
     */
    public function findByFolder(FolderId $folderId, OwnerId $ownerId): array;

    /**
     * Attachments in the folder regardless of trash state — backs trash/restore cascades.
     *
     * @return Attachment[]
     */
    public function findAllByFolder(FolderId $folderId, OwnerId $ownerId): array;

    /**
     * Attachments of the note that are not in trash — backs the note detail.
     *
     * @return Attachment[]
     */
    public function findByNote(NoteId $noteId, OwnerId $ownerId): array;

    /**
     * Attachments of the note regardless of trash state — backs trash/restore/delete cascades.
     *
     * @return Attachment[]
     */
    public function findAllByNote(NoteId $noteId, OwnerId $ownerId): array;

    /**
     * @return Attachment[]
     */
    public function findTrashed(OwnerId $ownerId): array;

    public function delete(Attachment $attachment): void;
}
