<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractIntValueObjectType;
use App\Notes\Domain\ValueObject\FileSizeBytes;

/**
 * @extends AbstractIntValueObjectType<FileSizeBytes>
 */
final class FileSizeBytesType extends AbstractIntValueObjectType
{
    public const string NAME = 'attachment_size_bytes';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return FileSizeBytes::class;
    }
}
