<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractTextValueObjectType;
use App\Notes\Domain\ValueObject\LinkNote;

/**
 * @extends AbstractTextValueObjectType<LinkNote>
 */
final class LinkNoteType extends AbstractTextValueObjectType
{
    public const string NAME = 'link_note';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return LinkNote::class;
    }
}
