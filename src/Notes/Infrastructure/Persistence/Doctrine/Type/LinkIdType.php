<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notes\Domain\ValueObject\LinkId;

/**
 * @extends AbstractStringValueObjectType<LinkId>
 */
final class LinkIdType extends AbstractStringValueObjectType
{
    public const string NAME = 'link_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return LinkId::class;
    }
}
