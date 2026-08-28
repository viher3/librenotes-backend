<?php

declare(strict_types=1);

namespace App\Notification\Application\Detail;

use App\Core\Application\Query\Query;

readonly class NotificationDetailQuery implements Query
{
    public function __construct(
        public string $id
    ) {
    }
}
