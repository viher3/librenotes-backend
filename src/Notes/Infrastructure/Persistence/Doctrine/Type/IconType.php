<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notes\Domain\ValueObject\Icon;

/**
 * @extends AbstractStringValueObjectType<Icon>
 */
final class IconType extends AbstractStringValueObjectType
{
    public const string NAME = 'notes_icon';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return Icon::class;
    }
}
