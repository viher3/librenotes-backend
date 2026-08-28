<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractTextValueObjectType;
use App\Notes\Domain\ValueObject\NoteContent;

/**
 * @extends AbstractTextValueObjectType<NoteContent>
 */
final class NoteContentType extends AbstractTextValueObjectType
{
    public const string NAME = 'note_content';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return NoteContent::class;
    }
}
