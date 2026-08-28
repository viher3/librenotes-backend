<?php

declare(strict_types=1);

namespace App\Notification\Domain\EventHandler;

use App\Core\Domain\Bus\Event\EventHandler;
use App\Notification\Domain\Event\NotificationQueued;

interface NotificationQueuedEventHandler extends EventHandler
{
    public function __invoke(NotificationQueued $event): void;
}
