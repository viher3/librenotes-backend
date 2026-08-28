<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\User\Domain\ValueObject\UserRoleName;

/**
 * @extends AbstractStringValueObjectType<UserRoleName>
 */
final class UserRoleNameType extends AbstractStringValueObjectType
{
    public const string NAME = 'user_role_name';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return UserRoleName::class;
    }
}
