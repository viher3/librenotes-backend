<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\User\Domain\Model\UserRole;
use App\User\Domain\Repository\UserRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<UserRole>
 */
class DoctrineUserRoleRepository extends DoctrineRepository implements UserRoleRepository
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry
    ) {
        parent::__construct(UserRole::class, $entityManager, $registry);
    }

    public function save(UserRole $role): void
    {
        $this->persist($role);
    }

    public function findById(string $id): ?UserRole
    {
        return $this->find($id);
    }

    public function findByName(string $name): ?UserRole
    {
        return $this->findOneBy(['name' => $name]);
    }
}
