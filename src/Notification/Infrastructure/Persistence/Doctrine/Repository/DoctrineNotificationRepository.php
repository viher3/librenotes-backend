<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\Notification\Domain\Model\Notification;
use App\Notification\Domain\Model\ValueObject\NotificationId;
use App\Notification\Domain\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<Notification>
 */
final class DoctrineNotificationRepository extends DoctrineRepository implements NotificationRepository
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry
    ) {
        parent::__construct(Notification::class, $entityManager, $registry);
    }

    public function save(Notification $notification): void
    {
        $this->persist($notification);
    }

    public function findById(NotificationId $id): ?Notification
    {
        return $this->find($id->value());
    }
}
