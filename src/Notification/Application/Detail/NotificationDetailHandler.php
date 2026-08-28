<?php

declare(strict_types=1);

namespace App\Notification\Application\Detail;

use App\Core\Application\Query\QueryHandler;
use App\Notification\Domain\Exception\NotificationNotFoundException;
use App\Notification\Domain\Model\ValueObject\NotificationId;
use App\Notification\Domain\Repository\NotificationRepository;

final class NotificationDetailHandler implements QueryHandler
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository
    ) {
    }

    public function __invoke(NotificationDetailQuery $query): NotificationDetailResponse
    {
        $notification = $this->notificationRepository->findById(new NotificationId($query->id));

        if (!$notification) {
            throw new NotificationNotFoundException($query->id);
        }

        return new NotificationDetailResponse($notification);
    }
}
