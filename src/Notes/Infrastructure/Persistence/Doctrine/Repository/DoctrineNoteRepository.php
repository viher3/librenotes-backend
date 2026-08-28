<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\Notes\Domain\Model\Note;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;
use Doctrine\DBAL\ParameterType;
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

    public function searchByTerm(string $term, OwnerId $ownerId, int $page, int $perPage): array
    {
        $ids = $this->entityManager()->getConnection()->fetchFirstColumn(
            <<<'SQL'
                SELECT id
                FROM note
                WHERE owner_id = :ownerId
                    AND deleted_at IS NULL
                    AND MATCH(title, content) AGAINST(:term IN NATURAL LANGUAGE MODE)
                ORDER BY MATCH(title, content) AGAINST(:term IN NATURAL LANGUAGE MODE) DESC
                LIMIT :limit OFFSET :offset
                SQL,
            [
                'ownerId' => $ownerId->value(),
                'term' => $term,
                'limit' => $perPage,
                'offset' => ($page - 1) * $perPage,
            ],
            [
                'ownerId' => ParameterType::STRING,
                'term' => ParameterType::STRING,
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ]
        );

        return $this->hydrateInOrder($ids);
    }

    public function searchByTermCount(string $term, OwnerId $ownerId): int
    {
        return (int) $this->entityManager()->getConnection()->fetchOne(
            <<<'SQL'
                SELECT COUNT(*)
                FROM note
                WHERE owner_id = :ownerId
                    AND deleted_at IS NULL
                    AND MATCH(title, content) AGAINST(:term IN NATURAL LANGUAGE MODE)
                SQL,
            [
                'ownerId' => $ownerId->value(),
                'term' => $term,
            ]
        );
    }

    /**
     * @param string[] $ids
     * @return Note[]
     */
    private function hydrateInOrder(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $notesById = [];
        foreach ($this->findBy(['id' => $ids]) as $note) {
            $notesById[$note->getId()->value()] = $note;
        }

        return array_values(array_filter(array_map(
            static fn (string $id) => $notesById[$id] ?? null,
            $ids
        )));
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
