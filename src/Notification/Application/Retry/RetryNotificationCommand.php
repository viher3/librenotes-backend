<?php

declare(strict_types=1);

namespace App\Notification\Application\Retry;

use App\Core\Application\Command\Command;

readonly class RetryNotificationCommand implements Command
{
    public function __construct(
        public string $id
    ) {
    }
}
