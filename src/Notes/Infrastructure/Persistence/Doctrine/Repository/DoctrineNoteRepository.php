<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\Notes\Domain\Model\Note;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;
use Doctrine\ORM\EntityManagerInterface;
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

        return $qb->getQuery()->getResult();
    }

    public function delete(Note $note): void
    {
        $this->remove($note);
    }
}
