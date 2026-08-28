<?php

declare(strict_types=1);

namespace App\Notification\Application\CreateTemplate;

use App\Core\Application\Command\Command;

readonly class CreateNotificationTemplateCommand implements Command
{
    public function __construct(
        public string $id,
        public string $key,
        public string $channel,
        public string $locale,
        public string $subject,
        public string $body,
        public bool $isActive = true
    ) {
    }
}
