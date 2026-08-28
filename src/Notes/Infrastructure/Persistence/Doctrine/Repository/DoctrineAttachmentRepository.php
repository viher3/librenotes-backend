<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<Attachment>
 */
final class DoctrineAttachmentRepository extends DoctrineRepository implements AttachmentRepository
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry
    ) {
        parent::__construct(Attachment::class, $entityManager, $registry);
    }

    public function save(Attachment $attachment): void
    {
        $this->persist($attachment);
    }

    public function getById(string $id): ?Attachment
    {
        return $this->find($id);
    }

    public function findByFolder(FolderId $folderId, OwnerId $ownerId): array
    {
        return $this->byFolderQueryBuilder($folderId, $ownerId)
            ->andWhere('a.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function findAllByFolder(FolderId $folderId, OwnerId $ownerId): array
    {
        return $this->byFolderQueryBuilder($folderId, $ownerId)
            ->getQuery()
            ->getResult();
    }

    public function findTrashed(OwnerId $ownerId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.ownerId = :ownerId')
            ->andWhere('a.deletedAt IS NOT NULL')
            ->setParameter('ownerId', $ownerId->value())
            ->orderBy('a.deletedAt', 'desc')
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
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function searchByTermQueryBuilder(string $term, OwnerId $ownerId): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.ownerId = :ownerId')
            ->andWhere('a.deletedAt IS NULL')
            ->andWhere('a.fileName LIKE :term')
            ->setParameter('ownerId', $ownerId->value())
            ->setParameter('term', '%' . self::escapeLikeTerm($term) . '%')
            ->orderBy('a.fileName', 'asc');
    }

    private function byFolderQueryBuilder(FolderId $folderId, OwnerId $ownerId): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.ownerId = :ownerId')
            ->andWhere('a.folderId = :folderId')
            ->setParameter('ownerId', $ownerId->value())
            ->setParameter('folderId', $folderId->value())
            ->orderBy('a.createdAt', 'desc');
    }

    public function delete(Attachment $attachment): void
    {
        $this->remove($attachment);
    }
}
