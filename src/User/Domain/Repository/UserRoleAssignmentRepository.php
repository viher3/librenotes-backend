<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Model\UserRoleAssignment;

interface UserRoleAssignmentRepository
{
    public function save(UserRoleAssignment $assignment): void;

    /**
     * @return UserRoleAssignment[]
     */
    public function findActiveByUserId(string $userId): array;

    public function findActiveByUserIdAndRoleId(string $userId, string $roleId): ?UserRoleAssignment;
}
