<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notes\Domain\ValueObject\AttachmentId;

/**
 * @extends AbstractStringValueObjectType<AttachmentId>
 */
final class AttachmentIdType extends AbstractStringValueObjectType
{
    public const string NAME = 'attachment_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return AttachmentId::class;
    }
}
