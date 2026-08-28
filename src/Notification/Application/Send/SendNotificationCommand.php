<?php

declare(strict_types=1);

namespace App\Notification\Application\Send;

use App\Core\Application\Command\Command;

readonly class SendNotificationCommand implements Command
{
    /**
     * @param array<string, scalar|array<mixed>|null> $payload
     */
    public function __construct(
        public string $id,
        public string $channel,
        public string $templateKey,
        public string $recipient,
        public ?string $recipientId = null,
        public array $payload = [],
        public ?string $locale = null
    ) {
    }
}
