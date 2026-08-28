<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notes\Domain\ValueObject\NoteTitle;

/**
 * @extends AbstractStringValueObjectType<NoteTitle>
 */
final class NoteTitleType extends AbstractStringValueObjectType
{
    public const string NAME = 'note_title';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return NoteTitle::class;
    }
}
