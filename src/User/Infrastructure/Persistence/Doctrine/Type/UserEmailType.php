<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\User\Domain\ValueObject\UserEmail;

/**
 * @extends AbstractStringValueObjectType<UserEmail>
 */
final class UserEmailType extends AbstractStringValueObjectType
{
    public const string NAME = 'user_email';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return UserEmail::class;
    }
}
