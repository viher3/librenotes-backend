<?php

declare(strict_types=1);

namespace App\Notification\Application\TemplateDetail;

use App\Core\Application\Query\Query;

readonly class NotificationTemplateDetailQuery implements Query
{
    public function __construct(
        public string $id
    ) {
    }
}
