<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\EventHandler;

use App\Core\Infrastructure\Bus\Command\SymfonyCommandBus;
use App\Notification\Application\Deliver\DeliverNotificationCommand;
use App\Notification\Domain\Event\NotificationQueued;
use App\Notification\Domain\EventHandler\NotificationQueuedEventHandler;

final class OnNotificationQueued implements NotificationQueuedEventHandler
{
    public function __construct(
        private readonly SymfonyCommandBus $commandBus
    ) {
    }

    public function __invoke(NotificationQueued $event): void
    {
        $this->commandBus->__invoke(new DeliverNotificationCommand($event->aggregateId()));
    }
}
