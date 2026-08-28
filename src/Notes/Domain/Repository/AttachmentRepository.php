<?php

declare(strict_types=1);

namespace App\Notes\Domain\Repository;

use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;

interface AttachmentRepository
{
    public function save(Attachment $attachment): void;

    public function getById(string $id): ?Attachment;

    /**
     * @return Attachment[]
     */
    public function findByFolder(FolderId $folderId, OwnerId $ownerId): array;

    public function delete(Attachment $attachment): void;
}
