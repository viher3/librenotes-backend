<?php

namespace App\Security\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\Security\Domain\Model\RefreshToken;
use App\Security\Domain\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<RefreshToken>
 */
class DoctrineRefreshTokenRepository extends DoctrineRepository implements RefreshTokenRepository
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry
    ) {
        parent::__construct(RefreshToken::class, $entityManager, $registry);
    }

    public function save(RefreshToken $refreshToken) : void
    {
        $this->persist($refreshToken);
    }

    public function findByTokenHash(string $tokenHash) : ?RefreshToken
    {
        return $this->findOneBy(['tokenHash' => $tokenHash]);
    }

    public function findActiveByUserId(string $userId) : array
    {
        return $this->findBy(['userId' => $userId, 'revoked' => false]);
    }
}