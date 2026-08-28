<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

final class NotificationDeliveryFailedException extends DomainError
{
    public function __construct(
        private readonly string $channel,
        string $reason
    ) {
        parent::__construct(sprintf(
            'Failed to deliver notification via "%s": %s',
            $this->channel,
            $reason
        ));
    }

    public function errorCode(): string
    {
        return 'notification.delivery_failed';
    }

    /**
     * @return array<string, string>
     */
    public function params(): array
    {
        return [
            'channel' => $this->channel,
        ];
    }
}
