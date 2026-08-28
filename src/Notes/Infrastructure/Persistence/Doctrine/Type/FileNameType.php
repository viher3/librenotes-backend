<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notes\Domain\ValueObject\FileName;

/**
 * @extends AbstractStringValueObjectType<FileName>
 */
final class FileNameType extends AbstractStringValueObjectType
{
    public const string NAME = 'attachment_file_name';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return FileName::class;
    }
}
