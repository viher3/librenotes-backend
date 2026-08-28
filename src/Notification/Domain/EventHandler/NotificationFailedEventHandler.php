<?php

declare(strict_types=1);

namespace App\Notification\Domain\EventHandler;

use App\Core\Domain\Bus\Event\EventHandler;
use App\Notification\Domain\Event\NotificationFailed;

interface NotificationFailedEventHandler extends EventHandler
{
    public function __invoke(NotificationFailed $event): void;
}
