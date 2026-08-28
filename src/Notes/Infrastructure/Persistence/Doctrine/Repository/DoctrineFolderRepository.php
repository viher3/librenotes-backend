<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;
use Doctrine\ORM\EntityManagerInterface;
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

        return $qb->getQuery()->getResult();
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
