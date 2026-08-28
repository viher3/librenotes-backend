<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notes\Domain\ValueObject\FolderId;

/**
 * @extends AbstractStringValueObjectType<FolderId>
 */
final class FolderIdType extends AbstractStringValueObjectType
{
    public const string NAME = 'folder_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return FolderId::class;
    }
}
