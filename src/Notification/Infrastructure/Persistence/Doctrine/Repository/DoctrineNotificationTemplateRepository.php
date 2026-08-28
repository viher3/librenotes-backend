<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine\Repository;

use App\Core\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use App\Notification\Domain\Model\NotificationTemplate;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Model\ValueObject\NotificationLocale;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateId;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateKey;
use App\Notification\Domain\Repository\NotificationTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<NotificationTemplate>
 */
final class DoctrineNotificationTemplateRepository extends DoctrineRepository implements NotificationTemplateRepository
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry
    ) {
        parent::__construct(NotificationTemplate::class, $entityManager, $registry);
    }

    public function save(NotificationTemplate $template): void
    {
        $this->persist($template);
    }

    public function delete(NotificationTemplate $template): void
    {
        $this->remove($template);
    }

    public function findById(NotificationTemplateId $id): ?NotificationTemplate
    {
        return $this->find($id->value());
    }

    public function findActiveByKeyChannelAndLocale(
        NotificationTemplateKey $key,
        NotificationChannel $channel,
        NotificationLocale $locale
    ): ?NotificationTemplate {
        return $this->findOneBy([
            'key' => $key->value(),
            'channel' => $channel->value(),
            'locale' => $locale->value(),
            'isActive' => true,
        ]);
    }

    public function existsByKeyChannelAndLocale(
        NotificationTemplateKey $key,
        NotificationChannel $channel,
        NotificationLocale $locale
    ): bool {
        return count($this->findBy([
            'key' => $key->value(),
            'channel' => $channel->value(),
            'locale' => $locale->value(),
        ])) > 0;
    }
}
