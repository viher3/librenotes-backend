<?php

declare(strict_types=1);

namespace App\Notification\Domain\Repository;

use App\Notification\Domain\Model\NotificationTemplate;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Model\ValueObject\NotificationLocale;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateId;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateKey;

interface NotificationTemplateRepository
{
    public function save(NotificationTemplate $template): void;

    public function delete(NotificationTemplate $template): void;

    public function findById(NotificationTemplateId $id): ?NotificationTemplate;

    public function findActiveByKeyChannelAndLocale(
        NotificationTemplateKey $key,
        NotificationChannel $channel,
        NotificationLocale $locale
    ): ?NotificationTemplate;

    public function existsByKeyChannelAndLocale(
        NotificationTemplateKey $key,
        NotificationChannel $channel,
        NotificationLocale $locale
    ): bool;

    /**
     * @param array<int, array<string, mixed>> $filters
     * @return NotificationTemplate[]
     */
    public function search(
        array $filters,
        int $page,
        int $perPage,
        ?string $orderBy = null,
        ?string $orderDirection = null
    ): array;

    /**
     * @param array<int, array<string, mixed>> $filters
     */
    public function searchCount(array $filters): int;
}
