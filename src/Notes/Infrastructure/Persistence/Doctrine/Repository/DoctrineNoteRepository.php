<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\Notes\Domain\Model\Note;
use App\Notes\Domain\Repository\NoteListCriteria;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\NoteId;
use App\Notes\Domain\ValueObject\OwnerId;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<Note>
 */
final class DoctrineNoteRepository extends DoctrineRepository implements NoteRepository
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry
    ) {
        parent::__construct(Note::class, $entityManager, $registry);
    }

    public function save(Note $note): void
    {
        $this->persist($note);
    }

    public function getById(string $id): ?Note
    {
        return $this->find($id);
    }

    public function findByFolder(?FolderId $folderId, OwnerId $ownerId): array
    {
        return $this->byFolderQueryBuilder($folderId, $ownerId)
            ->andWhere('n.deletedAt IS NULL')
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
            ->andWhere('n.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function findAllByParent(NoteId $parentId, OwnerId $ownerId): array
    {
        return $this->byParentQueryBuilder($parentId, $ownerId)
            ->getQuery()
            ->getResult();
    }

    public function countChildren(OwnerId $ownerId, array $noteIds): array
    {
        if ($noteIds === []) {
            return [];
        }

        $counts = array_fill_keys($noteIds, 0);
        $connection = $this->getEntityManager()->getConnection();
        $sql = <<<'SQL'
            SELECT parent_note_id AS parent, COUNT(*) AS children FROM (
                SELECT parent_note_id FROM note
                WHERE owner_id = :owner AND deleted_at IS NULL AND parent_note_id IN (:ids)
                UNION ALL
                SELECT parent_note_id FROM link
                WHERE owner_id = :owner AND deleted_at IS NULL AND parent_note_id IN (:ids)
            ) c GROUP BY parent_note_id
            SQL;

        $rows = $connection->fetchAllAssociative(
            $sql,
            ['owner' => $ownerId->value(), 'ids' => array_values($noteIds)],
            ['ids' => ArrayParameterType::STRING]
        );
        foreach ($rows as $row) {
            $counts[(string) $row['parent']] = (int) $row['children'];
        }

        return $counts;
    }

    public function findPage(NoteListCriteria $criteria): array
    {
        $where = ['n.owner_id = :owner', 'n.deleted_at IS NULL'];
        $params = ['owner' => $criteria->ownerId->value()];

        if ($criteria->rootOnly) {
            // The root holds what is in no folder and under no note (notes nested under a note have no folder).
            $where[] = 'n.folder_id IS NULL AND n.parent_note_id IS NULL';
        } elseif ($criteria->folderId !== null) {
            $where[] = 'n.folder_id = :folder';
            $params['folder'] = $criteria->folderId->value();
        } elseif ($criteria->parentNoteId !== null) {
            $where[] = 'n.parent_note_id = :parent';
            $params['parent'] = $criteria->parentNoteId->value();
        }
        if ($criteria->pinned !== null) {
            $where[] = 'n.pinned = :pinned';
            $params['pinned'] = $criteria->pinned ? 1 : 0;
        }
        if ($criteria->tag !== null) {
            $where[] = 'JSON_CONTAINS(n.tags, JSON_QUOTE(:tag))';
            $params['tag'] = $criteria->tag;
        }

        $orderBy = in_array($criteria->orderBy, NoteListCriteria::ORDER_FIELDS, true) ? $criteria->orderBy : 'updated_at';
        $direction = strtolower($criteria->orderDirection) === 'asc' ? 'ASC' : 'DESC';
        $size = max(1, min($criteria->size, NoteListCriteria::MAX_SIZE));
        $offset = (max(1, $criteria->page) - 1) * $size;
        $whereSql = implode(' AND ', $where);

        $connection = $this->getEntityManager()->getConnection();
        $total = (int) $connection->fetchOne("SELECT COUNT(*) FROM note n WHERE $whereSql", $params);
        /** @var string[] $ids */
        $ids = $connection->fetchFirstColumn(
            "SELECT n.id FROM note n WHERE $whereSql ORDER BY n.$orderBy $direction, n.id ASC LIMIT $size OFFSET $offset",
            $params
        );

        if ($ids === []) {
            return ['notes' => [], 'total' => $total];
        }

        $byId = [];
        foreach ($this->findBy(['id' => $ids]) as $note) {
            $byId[$note->getId()->value()] = $note;
        }

        return ['notes' => array_values(array_filter(array_map(static fn (string $id) => $byId[$id] ?? null, $ids))), 'total' => $total];
    }

    public function findTrashed(OwnerId $ownerId): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.ownerId = :ownerId')
            ->andWhere('n.deletedAt IS NOT NULL')
            ->setParameter('ownerId', $ownerId->value())
            ->orderBy('n.deletedAt', 'desc')
            ->getQuery()
            ->getResult();
    }

    private function byFolderQueryBuilder(?FolderId $folderId, OwnerId $ownerId): QueryBuilder
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId->value())
            ->orderBy('n.updatedAt', 'desc');

        if ($folderId === null) {
            $qb->andWhere('n.folderId IS NULL AND n.parentNoteId IS NULL');
        } else {
            $qb->andWhere('n.folderId = :folderId')
                ->setParameter('folderId', $folderId->value());
        }

        return $qb;
    }

    private function byParentQueryBuilder(NoteId $parentId, OwnerId $ownerId): QueryBuilder
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.ownerId = :ownerId')
            ->andWhere('n.parentNoteId = :parentId')
            ->setParameter('ownerId', $ownerId->value())
            ->setParameter('parentId', $parentId->value())
            ->orderBy('n.title', 'asc');
    }

    public function delete(Note $note): void
    {
        $this->remove($note);
    }
}
