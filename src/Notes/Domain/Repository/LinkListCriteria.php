<?php

declare(strict_types=1);

namespace App\Notes\Domain\Repository;

use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\NoteId;
use App\Notes\Domain\ValueObject\OwnerId;

readonly class LinkListCriteria
{
    public const array ORDER_FIELDS = ['updated_at', 'created_at', 'title'];

    /**
     * @param bool $rootOnly true = only links with neither folder nor parent note (takes precedence over the other filters)
     * @param ?string $tag   normalized tag name
     */
    public function __construct(
        public OwnerId $ownerId,
        public ?FolderId $folderId = null,
        public bool $rootOnly = false,
        public ?string $tag = null,
        public string $orderBy = 'updated_at',
        public string $orderDirection = 'desc',
        public int $page = 1,
        public int $size = 20,
        public ?NoteId $parentNoteId = null
    ) {
    }
}
