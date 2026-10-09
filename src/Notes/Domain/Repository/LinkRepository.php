<?php

declare(strict_types=1);

namespace App\Notes\Domain\Repository;

use App\Notes\Domain\Model\Link;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;

interface LinkRepository
{
    public function save(Link $link): void;

    public function getById(string $id): ?Link;

    /**
     * Links in the folder (null = root) that are not in trash — backs normal browsing.
     *
     * @return Link[]
     */
    public function findByFolder(?FolderId $folderId, OwnerId $ownerId): array;

    /**
     * Links in the folder regardless of trash state — backs trash/restore cascades.
     *
     * @return Link[]
     */
    public function findAllByFolder(?FolderId $folderId, OwnerId $ownerId): array;

    /**
     * One page of the owner's links that are not in trash, plus the total number of matches.
     *
     * @return array{links: Link[], total: int}
     */
    public function findPage(LinkListCriteria $criteria): array;

    /**
     * @return Link[]
     */
    public function findTrashed(OwnerId $ownerId): array;

    public function delete(Link $link): void;
}
