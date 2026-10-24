<?php

declare(strict_types=1);

namespace App\Notes\Domain\Repository;

use App\Notes\Domain\Model\Link;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\NoteId;
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
     * Links directly under a note that are not in trash — backs browsing the tree.
     *
     * @return Link[]
     */
    public function findByParent(NoteId $parentId, OwnerId $ownerId): array;

    /**
     * Links directly under a note regardless of trash state — backs trash/restore/delete cascades.
     *
     * @return Link[]
     */
    public function findAllByParent(NoteId $parentId, OwnerId $ownerId): array;

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

    /**
     * Links not in trash whose title or URL matches the search term.
     *
     * @return Link[]
     */
    public function searchByTerm(string $term, OwnerId $ownerId, int $page, int $perPage): array;

    public function searchByTermCount(string $term, OwnerId $ownerId): int;

    public function delete(Link $link): void;
}
