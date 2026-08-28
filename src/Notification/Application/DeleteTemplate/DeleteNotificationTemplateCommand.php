<?php

declare(strict_types=1);

namespace App\Notification\Application\DeleteTemplate;

use App\Core\Application\Command\Command;

readonly class DeleteNotificationTemplateCommand implements Command
{
    public function __construct(
        public string $id
    ) {
    }
}
