<?php

declare(strict_types=1);

namespace App\Notification\Application\Retry;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Bus\Event\EventBus;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Notification\Domain\Exception\NotificationNotFoundException;
use App\Notification\Domain\Model\ValueObject\NotificationId;
use App\Notification\Domain\Repository\NotificationRepository;

final class RetryNotificationHandler implements TransactionalCommandHandler
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly EventBus $eventBus,
        private readonly DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(RetryNotificationCommand $command): void
    {
        $notification = $this->notificationRepository->findById(new NotificationId($command->id));

        if (!$notification) {
            throw new NotificationNotFoundException($command->id);
        }

        $notification->retry(new DateTimeValueObject($this->dateTimeProvider->now()));

        $this->notificationRepository->save($notification);

        $this->eventBus->publish(...$notification->pullDomainEvents());
    }
}
