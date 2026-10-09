<?php

declare(strict_types=1);

namespace App\Notes\Domain\Repository;

use App\Notes\Domain\ValueObject\OwnerId;

readonly class SearchCriteria
{
    public const string TYPE_NOTE = 'note';
    public const string TYPE_LINK = 'link';
    public const array TYPES = [self::TYPE_NOTE, self::TYPE_LINK];

    /**
     * @param string[] $terms  lower-cased, whitespace-separated search terms (all must match)
     * @param string[] $types  subset of TYPES
     * @param ?string $tag     normalized tag name
     */
    public function __construct(
        public OwnerId $ownerId,
        public array $terms,
        public array $types,
        public ?string $tag,
        public int $page,
        public int $size
    ) {
    }
}
