<?php

namespace App\User\Domain\ValueObject;

use App\Core\Domain\ValueObject\IntValueObject;
use App\User\Domain\UserRoleValue;

class UserRole extends IntValueObject
{
    public const array VALUES = [
        UserRoleValue::REGULAR->value,
        UserRoleValue::ADMIN->value
    ];

    public const array USER_ROLE_LABEL = [
        UserRoleValue::REGULAR->value => 'Regular',
        UserRoleValue::ADMIN->value => 'Admin'
    ];

    public function __construct(int $value)
    {
        if(!in_array($value, self::VALUES)){
            throw new \RuntimeException('Invalid UserRole: ' . $value);
        }

        parent::__construct($value);
    }
}