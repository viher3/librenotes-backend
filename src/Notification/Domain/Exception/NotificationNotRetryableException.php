<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

final class NotificationNotRetryableException extends DomainError
{
    public function __construct(
        private readonly string $id,
        private readonly string $status
    ) {
        parent::__construct(sprintf(
            'Notification %s is not retryable from status "%s"',
            $this->id,
            $this->status
        ));
    }

    public function errorCode(): string
    {
        return 'notification.not_retryable';
    }

    /**
     * @return array<string, string>
     */
    public function params(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
        ];
    }
}
