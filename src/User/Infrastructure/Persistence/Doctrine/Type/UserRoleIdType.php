<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\User\Domain\ValueObject\UserRoleId;

/**
 * @extends AbstractStringValueObjectType<UserRoleId>
 */
final class UserRoleIdType extends AbstractStringValueObjectType
{
    public const string NAME = 'user_role_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return UserRoleId::class;
    }
}
