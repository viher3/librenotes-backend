<?php

declare(strict_types=1);

namespace App\Notification\Domain\Repository;

use App\Notification\Domain\Model\Notification;
use App\Notification\Domain\Model\ValueObject\NotificationId;

interface NotificationRepository
{
    public function save(Notification $notification): void;

    public function findById(NotificationId $id): ?Notification;

    /**
     * @param array<int, array<string, mixed>> $filters
     * @return Notification[]
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
