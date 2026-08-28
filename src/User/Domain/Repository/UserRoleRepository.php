<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Model\UserRole;

interface UserRoleRepository
{
    public function save(UserRole $role): void;

    public function findById(string $id): ?UserRole;

    public function findByName(string $name): ?UserRole;
}
