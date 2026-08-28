<?php

declare(strict_types=1);

namespace App\Notification\Application\Deliver;

use App\Core\Application\Command\Command;

readonly class DeliverNotificationCommand implements Command
{
    public function __construct(
        public string $id
    ) {
    }
}
