<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notes\Domain\ValueObject\FolderName;

/**
 * @extends AbstractStringValueObjectType<FolderName>
 */
final class FolderNameType extends AbstractStringValueObjectType
{
    public const string NAME = 'folder_name';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return FolderName::class;
    }
}
