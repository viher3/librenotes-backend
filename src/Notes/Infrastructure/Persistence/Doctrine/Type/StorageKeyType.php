<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notes\Domain\ValueObject\StorageKey;

/**
 * @extends AbstractStringValueObjectType<StorageKey>
 */
final class StorageKeyType extends AbstractStringValueObjectType
{
    public const string NAME = 'attachment_storage_key';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return StorageKey::class;
    }
}
