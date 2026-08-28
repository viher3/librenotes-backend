<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\EventHandler;

use App\Notification\Domain\Event\NotificationFailed;
use App\Notification\Domain\EventHandler\NotificationFailedEventHandler;
use Psr\Log\LoggerInterface;

final class OnNotificationFailed implements NotificationFailedEventHandler
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(NotificationFailed $event): void
    {
        $this->logger->error('Notification delivery failed', [
            'id' => $event->aggregateId(),
            'channel' => $event->channel(),
            'recipient' => $event->recipient(),
            'attempts' => $event->attempts(),
            'error' => $event->error(),
        ]);
    }
}
