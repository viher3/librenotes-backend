<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Persistence\Doctrine\Repository;

use App\Configuration\Domain\Model\ConfigurationHistory;
use App\Configuration\Domain\Model\ValueObject\ConfigurationHistoryId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Repository\ConfigurationHistoryRepository;
use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<ConfigurationHistory>
 */
final class DoctrineConfigurationHistoryRepository extends DoctrineRepository implements ConfigurationHistoryRepository
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry
    ) {
        parent::__construct(ConfigurationHistory::class, $entityManager, $registry);
    }

    public function save(ConfigurationHistory $entry): void
    {
        $this->persist($entry);
    }

    public function findByConfigurationKey(ConfigurationKey $key, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('h')
            ->where('h.configurationKey = :key')
            ->setParameter('key', $key->value())
            ->orderBy('h.changedAt', 'desc')
            ->setMaxResults($perPage)
            ->setFirstResult($perPage * ($page - 1));

        return $qb->getQuery()->getResult();
    }

    public function countByConfigurationKey(ConfigurationKey $key): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('count(h.id)')
            ->where('h.configurationKey = :key')
            ->setParameter('key', $key->value())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findSensitiveNotOnKeyVersion(int $targetVersion, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('h')
            ->where('(h.previousValueKeyVersion IS NOT NULL AND h.previousValueKeyVersion != :target)')
            ->orWhere('(h.newValueKeyVersion IS NOT NULL AND h.newValueKeyVersion != :target)')
            ->setParameter('target', $targetVersion)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    public function rotateEncryptedValues(
        ConfigurationHistoryId $id,
        ?string $previousCiphertext,
        ?int $previousKeyVersion,
        ?string $newCiphertext,
        ?int $newKeyVersion
    ): void {
        $this->entityManager()->getConnection()->executeStatement(
            'UPDATE configuration_history
             SET previous_value = :previousValue,
                 previous_value_key_version = :previousKeyVersion,
                 new_value = :newValue,
                 new_value_key_version = :newKeyVersion
             WHERE id = :id',
            [
                'previousValue' => $previousCiphertext,
                'previousKeyVersion' => $previousKeyVersion,
                'newValue' => $newCiphertext,
                'newKeyVersion' => $newKeyVersion,
                'id' => $id->value(),
            ]
        );
    }
}
