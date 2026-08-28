<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<Folder>
 */
final class DoctrineFolderRepository extends DoctrineRepository implements FolderRepository
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry
    ) {
        parent::__construct(Folder::class, $entityManager, $registry);
    }

    public function save(Folder $folder): void
    {
        $this->persist($folder);
    }

    public function getById(string $id): ?Folder
    {
        return $this->find($id);
    }

    public function findChildren(?FolderId $parentFolderId, OwnerId $ownerId): array
    {
        return $this->childrenQueryBuilder($parentFolderId, $ownerId)
            ->andWhere('f.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function findAllChildren(?FolderId $parentFolderId, OwnerId $ownerId): array
    {
        return $this->childrenQueryBuilder($parentFolderId, $ownerId)
            ->getQuery()
            ->getResult();
    }

    public function findTrashed(OwnerId $ownerId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.ownerId = :ownerId')
            ->andWhere('f.deletedAt IS NOT NULL')
            ->setParameter('ownerId', $ownerId->value())
            ->orderBy('f.deletedAt', 'desc')
            ->getQuery()
            ->getResult();
    }

    public function searchByTerm(string $term, OwnerId $ownerId, int $page, int $perPage): array
    {
        return $this->searchByTermQueryBuilder($term, $ownerId)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function searchByTermCount(string $term, OwnerId $ownerId): int
    {
        return (int) $this->searchByTermQueryBuilder($term, $ownerId)
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function searchByTermQueryBuilder(string $term, OwnerId $ownerId): QueryBuilder
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.ownerId = :ownerId')
            ->andWhere('f.deletedAt IS NULL')
            ->andWhere('f.name LIKE :term')
            ->setParameter('ownerId', $ownerId->value())
            ->setParameter('term', '%' . self::escapeLikeTerm($term) . '%')
            ->orderBy('f.name', 'asc');
    }

    private function childrenQueryBuilder(?FolderId $parentFolderId, OwnerId $ownerId): QueryBuilder
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId->value())
            ->orderBy('f.name', 'asc');

        if ($parentFolderId === null) {
            $qb->andWhere('f.parentFolderId IS NULL');
        } else {
            $qb->andWhere('f.parentFolderId = :parentFolderId')
                ->setParameter('parentFolderId', $parentFolderId->value());
        }

        return $qb;
    }

    public function isDescendantOf(FolderId $candidate, FolderId $ancestor): bool
    {
        $current = $this->getById($candidate->value());

        while ($current !== null && $current->getParentFolderId() !== null) {
            if ($current->getParentFolderId()->equals($ancestor)) {
                return true;
            }

            $current = $this->getById($current->getParentFolderId()->value());
        }

        return false;
    }

    public function delete(Folder $folder): void
    {
        $this->remove($folder);
    }
}
