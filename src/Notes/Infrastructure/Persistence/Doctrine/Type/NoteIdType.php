<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notes\Domain\ValueObject\NoteId;

/**
 * @extends AbstractStringValueObjectType<NoteId>
 */
final class NoteIdType extends AbstractStringValueObjectType
{
    public const string NAME = 'note_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return NoteId::class;
    }
}
