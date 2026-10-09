<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\NoteId;
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

    public function findByNote(NoteId $noteId, OwnerId $ownerId): array
    {
        return $this->byNoteQueryBuilder($noteId, $ownerId)
            ->andWhere('a.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function findAllByNote(NoteId $noteId, OwnerId $ownerId): array
    {
        return $this->byNoteQueryBuilder($noteId, $ownerId)
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

    private function byNoteQueryBuilder(NoteId $noteId, OwnerId $ownerId): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.ownerId = :ownerId')
            ->andWhere('a.noteId = :noteId')
            ->setParameter('ownerId', $ownerId->value())
            ->setParameter('noteId', $noteId->value())
            ->orderBy('a.createdAt', 'desc');
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
