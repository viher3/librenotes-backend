<?php

declare(strict_types=1);

namespace App\Notification\Domain\Service;

final readonly class RenderedNotification
{
    public function __construct(
        public string $subject,
        public string $body
    ) {
    }
}
