<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\Notes\Domain\Model\Note;
use App\Notes\Domain\Repository\NoteListCriteria;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;
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

    public function findPage(NoteListCriteria $criteria): array
    {
        $where = ['n.owner_id = :owner', 'n.deleted_at IS NULL'];
        $params = ['owner' => $criteria->ownerId->value()];

        if ($criteria->rootOnly) {
            $where[] = 'n.folder_id IS NULL';
        } elseif ($criteria->folderId !== null) {
            $where[] = 'n.folder_id = :folder';
            $params['folder'] = $criteria->folderId->value();
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
            $qb->andWhere('n.folderId IS NULL');
        } else {
            $qb->andWhere('n.folderId = :folderId')
                ->setParameter('folderId', $folderId->value());
        }

        return $qb;
    }

    public function delete(Note $note): void
    {
        $this->remove($note);
    }
}
