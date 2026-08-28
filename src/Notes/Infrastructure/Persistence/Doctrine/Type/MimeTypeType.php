<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notes\Domain\ValueObject\MimeType;

/**
 * @extends AbstractStringValueObjectType<MimeType>
 */
final class MimeTypeType extends AbstractStringValueObjectType
{
    public const string NAME = 'attachment_mime_type';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return MimeType::class;
    }
}
