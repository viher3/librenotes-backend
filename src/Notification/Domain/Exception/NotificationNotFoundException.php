<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exception;

use App\Core\Domain\Exception\NotFoundException;

final class NotificationNotFoundException extends NotFoundException
{
    public function __construct(private readonly string $id)
    {
        parent::__construct($this->id);
    }

    public function errorMessage(): string
    {
        return 'Notification not found: ' . $this->id;
    }

    public function errorCode(): string
    {
        return 'notification.not_found';
    }
}
