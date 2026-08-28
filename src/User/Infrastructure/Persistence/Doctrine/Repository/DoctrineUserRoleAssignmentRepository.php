<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\User\Domain\Model\UserRoleAssignment;
use App\User\Domain\Repository\UserRoleAssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<UserRoleAssignment>
 */
class DoctrineUserRoleAssignmentRepository extends DoctrineRepository implements UserRoleAssignmentRepository
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry
    ) {
        parent::__construct(UserRoleAssignment::class, $entityManager, $registry);
    }

    public function save(UserRoleAssignment $assignment): void
    {
        $this->persist($assignment);
    }

    public function findActiveByUserId(string $userId): array
    {
        return $this->findBy([
            'userId' => $userId,
            'revokedAt' => null,
        ]);
    }

    public function findActiveByUserIdAndRoleId(string $userId, string $roleId): ?UserRoleAssignment
    {
        return $this->findOneBy([
            'userId' => $userId,
            'userRoleId' => $roleId,
            'revokedAt' => null,
        ]);
    }
}
