<?php

declare(strict_types=1);

namespace App\Notification\Application\Send;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Bus\Event\EventBus;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Core\Domain\ValueObject\Uuid;
use App\Notification\Domain\Model\Notification;
use App\Notification\Domain\Model\ValueObject\NotificationAttempts;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Model\ValueObject\NotificationId;
use App\Notification\Domain\Model\ValueObject\NotificationLocale;
use App\Notification\Domain\Model\ValueObject\NotificationPayload;
use App\Notification\Domain\Model\ValueObject\NotificationRecipient;
use App\Notification\Domain\Model\ValueObject\NotificationStatus;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateKey;
use App\Notification\Domain\Repository\NotificationRepository;

/**
 * Only persists — never renders or sends. Delivery happens in DeliverNotificationHandler,
 * reacting to the NotificationQueued event this raises. Keeping the two apart is what lets
 * delivery move to an async transport later by editing messenger.yaml alone.
 */
final class SendNotificationHandler implements TransactionalCommandHandler
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly EventBus $eventBus,
        private readonly DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(SendNotificationCommand $command): void
    {
        $now = new DateTimeValueObject($this->dateTimeProvider->now());

        $notification = new Notification(
            id: new NotificationId($command->id),
            channel: new NotificationChannel($command->channel),
            templateKey: new NotificationTemplateKey($command->templateKey),
            locale: new NotificationLocale($command->locale ?? NotificationLocale::DEFAULT),
            recipient: new NotificationRecipient($command->recipient),
            recipientId: $command->recipientId !== null ? new Uuid($command->recipientId) : null,
            payload: new NotificationPayload($command->payload),
            subject: null,
            body: null,
            status: new NotificationStatus(NotificationStatus::PENDING),
            attempts: new NotificationAttempts(0),
            lastError: null,
            providerMessageId: null,
            sentAt: null,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->notificationRepository->save($notification);

        $this->eventBus->publish(...$notification->pullDomainEvents());
    }
}
