<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\User\Domain\ValueObject\Username;

/**
 * @extends AbstractStringValueObjectType<Username>
 */
final class UsernameType extends AbstractStringValueObjectType
{
    public const string NAME = 'username';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return Username::class;
    }
}
