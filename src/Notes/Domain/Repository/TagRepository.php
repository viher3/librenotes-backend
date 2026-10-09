<?php

declare(strict_types=1);

namespace App\Notes\Domain\Repository;

use App\Notes\Domain\ValueObject\OwnerId;

interface TagRepository
{
    /**
     * Tags in use by the owner's notes and links that are not in trash, most used first.
     *
     * @return list<array{name: string, count: int}>
     */
    public function countByOwner(OwnerId $ownerId): array;
}
