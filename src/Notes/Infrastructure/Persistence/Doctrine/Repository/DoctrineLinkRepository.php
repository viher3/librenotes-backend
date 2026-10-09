<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\Notes\Domain\Model\Link;
use App\Notes\Domain\Repository\LinkListCriteria;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\NoteId;
use App\Notes\Domain\ValueObject\OwnerId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<Link>
 */
final class DoctrineLinkRepository extends DoctrineRepository implements LinkRepository
{
    private const int MAX_PAGE_SIZE = 100;

    public function __construct(
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry
    ) {
        parent::__construct(Link::class, $entityManager, $registry);
    }

    public function save(Link $link): void
    {
        $this->persist($link);
    }

    public function getById(string $id): ?Link
    {
        return $this->find($id);
    }

    public function findByFolder(?FolderId $folderId, OwnerId $ownerId): array
    {
        return $this->byFolderQueryBuilder($folderId, $ownerId)
            ->andWhere('l.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function findAllByFolder(?FolderId $folderId, OwnerId $ownerId): array
    {
        return $this->byFolderQueryBuilder($folderId, $ownerId)
            ->getQuery()
            ->getResult();
    }

    public function findByParent(NoteId $parentId, OwnerId $ownerId): array
    {
        return $this->byParentQueryBuilder($parentId, $ownerId)
            ->andWhere('l.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function findAllByParent(NoteId $parentId, OwnerId $ownerId): array
    {
        return $this->byParentQueryBuilder($parentId, $ownerId)
            ->getQuery()
            ->getResult();
    }

    public function findPage(LinkListCriteria $criteria): array
    {
        $where = ['l.owner_id = :owner', 'l.deleted_at IS NULL'];
        $params = ['owner' => $criteria->ownerId->value()];

        if ($criteria->rootOnly) {
            $where[] = 'l.folder_id IS NULL AND l.parent_note_id IS NULL';
        } elseif ($criteria->folderId !== null) {
            $where[] = 'l.folder_id = :folder';
            $params['folder'] = $criteria->folderId->value();
        } elseif ($criteria->parentNoteId !== null) {
            $where[] = 'l.parent_note_id = :parent';
            $params['parent'] = $criteria->parentNoteId->value();
        }
        if ($criteria->tag !== null) {
            $where[] = 'JSON_CONTAINS(l.tags, JSON_QUOTE(:tag))';
            $params['tag'] = $criteria->tag;
        }

        $orderBy = in_array($criteria->orderBy, LinkListCriteria::ORDER_FIELDS, true) ? $criteria->orderBy : 'updated_at';
        $direction = strtolower($criteria->orderDirection) === 'asc' ? 'ASC' : 'DESC';
        $size = max(1, min($criteria->size, self::MAX_PAGE_SIZE));
        $offset = (max(1, $criteria->page) - 1) * $size;
        $whereSql = implode(' AND ', $where);

        $connection = $this->getEntityManager()->getConnection();
        $total = (int) $connection->fetchOne("SELECT COUNT(*) FROM link l WHERE $whereSql", $params);
        /** @var string[] $ids */
        $ids = $connection->fetchFirstColumn(
            "SELECT l.id FROM link l WHERE $whereSql ORDER BY l.$orderBy $direction, l.id ASC LIMIT $size OFFSET $offset",
            $params
        );

        if ($ids === []) {
            return ['links' => [], 'total' => $total];
        }

        $byId = [];
        foreach ($this->findBy(['id' => $ids]) as $link) {
            $byId[$link->getId()->value()] = $link;
        }

        return [
            'links' => array_values(array_filter(array_map(static fn (string $id) => $byId[$id] ?? null, $ids))),
            'total' => $total,
        ];
    }

    public function findTrashed(OwnerId $ownerId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.ownerId = :ownerId')
            ->andWhere('l.deletedAt IS NOT NULL')
            ->setParameter('ownerId', $ownerId->value())
            ->orderBy('l.deletedAt', 'desc')
            ->getQuery()
            ->getResult();
    }

    private function byParentQueryBuilder(NoteId $parentId, OwnerId $ownerId): QueryBuilder
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.ownerId = :ownerId')
            ->andWhere('l.parentNoteId = :parentId')
            ->setParameter('ownerId', $ownerId->value())
            ->setParameter('parentId', $parentId->value())
            ->orderBy('l.title', 'asc');
    }

    public function delete(Link $link): void
    {
        $this->remove($link);
    }

    private function byFolderQueryBuilder(?FolderId $folderId, OwnerId $ownerId): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('l')
            ->andWhere('l.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId->value())
            ->orderBy('l.updatedAt', 'desc');

        if ($folderId === null) {
            return $queryBuilder->andWhere('l.folderId IS NULL AND l.parentNoteId IS NULL');
        }

        return $queryBuilder
            ->andWhere('l.folderId = :folderId')
            ->setParameter('folderId', $folderId->value());
    }
}
