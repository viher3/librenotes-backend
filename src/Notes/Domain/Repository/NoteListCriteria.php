<?php

declare(strict_types=1);

namespace App\Notes\Domain\Repository;

use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;

readonly class NoteListCriteria
{
    public const array ORDER_FIELDS = ['updated_at', 'created_at', 'title'];
    public const int DEFAULT_SIZE = 20;
    public const int MAX_SIZE = 100;

    /**
     * @param bool $rootOnly   true = only notes without a folder (takes precedence over $folderId)
     * @param ?string $tag     normalized tag name
     */
    public function __construct(
        public OwnerId $ownerId,
        public ?FolderId $folderId = null,
        public bool $rootOnly = false,
        public ?string $tag = null,
        public ?bool $pinned = null,
        public string $orderBy = 'updated_at',
        public string $orderDirection = 'desc',
        public int $page = 1,
        public int $size = self::DEFAULT_SIZE
    ) {
    }
}
