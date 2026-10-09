<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notes\Domain\ValueObject\LinkTitle;

/**
 * @extends AbstractStringValueObjectType<LinkTitle>
 */
final class LinkTitleType extends AbstractStringValueObjectType
{
    public const string NAME = 'link_title';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return LinkTitle::class;
    }
}
