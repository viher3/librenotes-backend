<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exception;

use App\Core\Domain\Exception\NotFoundException;

/**
 * Raised both when a template lookup by id fails (CRUD endpoints) and when delivery
 * can't find an active template for a (key, channel, locale) triple — hence the
 * generic "identifier" rather than "id" or "key".
 */
final class NotificationTemplateNotFoundException extends NotFoundException
{
    public function __construct(private readonly string $identifier)
    {
        parent::__construct($this->identifier);
    }

    public function errorMessage(): string
    {
        return 'Notification template not found: ' . $this->identifier;
    }

    public function errorCode(): string
    {
        return 'notification.template_not_found';
    }
}
