<?php

namespace App\User\Domain;

enum UserRoleValue: int
{
    case REGULAR = 1;
    case ADMIN = 2;

}