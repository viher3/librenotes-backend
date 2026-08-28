<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;
use Doctrine\ORM\EntityManagerInterface;
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
        return $this->createQueryBuilder('a')
            ->andWhere('a.ownerId = :ownerId')
            ->andWhere('a.folderId = :folderId')
            ->setParameter('ownerId', $ownerId->value())
            ->setParameter('folderId', $folderId->value())
            ->orderBy('a.createdAt', 'desc')
            ->getQuery()
            ->getResult();
    }

    public function delete(Attachment $attachment): void
    {
        $this->remove($attachment);
    }
}
