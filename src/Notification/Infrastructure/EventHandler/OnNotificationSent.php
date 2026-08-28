<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\EventHandler;

use App\Notification\Domain\Event\NotificationSent;
use App\Notification\Domain\EventHandler\NotificationSentEventHandler;
use Psr\Log\LoggerInterface;

final class OnNotificationSent implements NotificationSentEventHandler
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(NotificationSent $event): void
    {
        $this->logger->info('Notification sent', [
            'id' => $event->aggregateId(),
            'channel' => $event->channel(),
            'recipient' => $event->recipient(),
            'providerMessageId' => $event->providerMessageId(),
        ]);
    }
}
