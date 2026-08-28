<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notes\Domain\ValueObject\OwnerId;

/**
 * @extends AbstractStringValueObjectType<OwnerId>
 */
final class NotesOwnerIdType extends AbstractStringValueObjectType
{
    public const string NAME = 'notes_owner_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return OwnerId::class;
    }
}
