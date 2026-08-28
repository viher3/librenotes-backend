<?php

declare(strict_types=1);

namespace App\Notification\Application\UpdateTemplate;

use App\Core\Application\Command\Command;

readonly class UpdateNotificationTemplateCommand implements Command
{
    public function __construct(
        public string $id,
        public ?string $subject = null,
        public ?string $body = null,
        public ?bool $isActive = null
    ) {
    }
}
