<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

final class UnsupportedNotificationChannelException extends DomainError
{
    public function __construct(private readonly string $channel)
    {
        parent::__construct('Unsupported notification channel: ' . $this->channel);
    }

    public function errorCode(): string
    {
        return 'notification.unsupported_channel';
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
