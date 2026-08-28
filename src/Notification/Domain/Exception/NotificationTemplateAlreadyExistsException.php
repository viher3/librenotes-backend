<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

final class NotificationTemplateAlreadyExistsException extends DomainError
{
    public function __construct(
        private readonly string $key,
        private readonly string $channel,
        private readonly string $locale
    ) {
        parent::__construct(sprintf(
            'Notification template already exists: %s (%s/%s)',
            $this->key,
            $this->channel,
            $this->locale
        ));
    }

    public function errorCode(): string
    {
        return 'notification.template_already_exists';
    }

    /**
     * @return array<string, string>
     */
    public function params(): array
    {
        return [
            'key' => $this->key,
            'channel' => $this->channel,
            'locale' => $this->locale,
        ];
    }
}
