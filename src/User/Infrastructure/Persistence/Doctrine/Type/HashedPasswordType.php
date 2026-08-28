<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\User\Domain\ValueObject\HashedPassword;

/**
 * @extends AbstractStringValueObjectType<HashedPassword>
 */
final class HashedPasswordType extends AbstractStringValueObjectType
{
    public const string NAME = 'hashed_password';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return HashedPassword::class;
    }
}
