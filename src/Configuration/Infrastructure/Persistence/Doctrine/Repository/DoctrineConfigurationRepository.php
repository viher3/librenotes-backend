<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Persistence\Doctrine\Repository;

use App\Configuration\Domain\Model\Configuration;
use App\Configuration\Domain\Model\ValueObject\ConfigurationId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Repository\ConfigurationRepository;
use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<Configuration>
 */
final class DoctrineConfigurationRepository extends DoctrineRepository implements ConfigurationRepository
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry
    ) {
        parent::__construct(Configuration::class, $entityManager, $registry);
    }

    public function save(Configuration $configuration): void
    {
        $this->persist($configuration);
    }

    public function findByKey(ConfigurationKey $key): ?Configuration
    {
        return $this->findOneBy(['key' => $key->value()]);
    }

    public function findById(ConfigurationId $id): ?Configuration
    {
        return $this->find($id->value());
    }

    public function delete(Configuration $configuration): void
    {
        $this->remove($configuration);
    }

    public function existsByKey(ConfigurationKey $key): bool
    {
        return count($this->findBy(['key' => $key->value()])) > 0;
    }

    public function findSensitiveNotOnKeyVersion(int $targetVersion, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.keyVersion IS NOT NULL')
            ->andWhere('c.keyVersion != :target')
            ->setParameter('target', $targetVersion)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    public function rotateEncryptedValue(ConfigurationId $id, string $ciphertext, int $keyVersion): void
    {
        $this->entityManager()->getConnection()->executeStatement(
            'UPDATE configurations SET value = :value, key_version = :keyVersion WHERE id = :id',
            [
                'value' => $ciphertext,
                'keyVersion' => $keyVersion,
                'id' => $id->value(),
            ]
        );
    }
}
