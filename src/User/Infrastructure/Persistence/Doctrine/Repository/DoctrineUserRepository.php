<?php

namespace App\User\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\User\Domain\CheckUserByEmailInterface;
use App\User\Domain\Model\User;
use App\User\Domain\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<User>
 */
class DoctrineUserRepository extends DoctrineRepository implements UserRepository, CheckUserByEmailInterface
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry
    )
    {
        parent::__construct(User::class, $entityManager, $registry);
    }

    public function search(
        array $filters,
        int $page,
        int $perPage,
        ?string $orderBy = null,
        ?string $orderDirection = null
    ): array
    {
        $qb = $this->customSearchQueryBuilder($filters, $page, $perPage, $orderBy, $orderDirection);
        return $qb->getQuery()->getResult();
    }

    private function customSearchQueryBuilder(
        array $filters,
        int $page,
        int $perPage,
        ?string $orderBy = null,
        ?string $orderDirection = null
    ) : QueryBuilder
    {
        $qb = $this->searchQueryBuilder($filters, $page, $perPage, $orderBy, $orderDirection);
        return $qb;
    }

    public function save(User $user): void
    {
        $this->persist($user);
    }

    public function getById(string $id): ?User
    {
        return $this->find($id);
    }

    public function isEmailAvailable(string $email): bool
    {
        return count($this->findBy(['email' => $email])) > 0;
    }

    public function findByUsername(string $username) : ?User
    {
        return $this->findOneBy([
            'username' => $username,
        ]);
    }

    public function findByEmail(string $email) : ?User
    {
        return $this->findOneBy([
            'email' => $email
        ]);
    }

    public function findByActivationToken(string $token): ?User
    {
        return $this->findOneBy([
            'activationToken' => $token,
        ]);
    }

    public function delete(User $user) : void
    {
        $this->remove($user);
    }
}