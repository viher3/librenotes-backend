<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notes\Domain\ValueObject\LinkUrl;

/**
 * @extends AbstractStringValueObjectType<LinkUrl>
 */
final class LinkUrlType extends AbstractStringValueObjectType
{
    public const string NAME = 'link_url';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return LinkUrl::class;
    }
}
